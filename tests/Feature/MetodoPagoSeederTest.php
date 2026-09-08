<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use Database\Seeders\MetodoPagoSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetodoPagoSeederTest extends TestCase
{
    private const METODOS = [
        'EFECTIVO' => ['Efectivo', '01', 10, []],
        'CHEQUE_NOMINATIVO' => ['Cheque nominativo', '02', 20, ['requiere_banco', 'requiere_numero_cheque', 'requiere_comprobante']],
        'TRANSFERENCIA_SPEI' => ['Transferencia SPEI', '03', 30, ['requiere_banco', 'requiere_referencia', 'requiere_rastreo_spei', 'requiere_comprobante']],
        'TARJETA_CREDITO' => ['Tarjeta de crédito', '04', 40, ['requiere_autorizacion', 'requiere_terminal', 'requiere_ultimos_4_digitos']],
        'MONEDERO_ELECTRONICO' => ['Monedero electrónico', '05', 50, ['requiere_proveedor', 'requiere_referencia']],
        'DINERO_ELECTRONICO' => ['Dinero electrónico', '06', 60, ['requiere_proveedor', 'requiere_referencia']],
        'TARJETA_DEBITO' => ['Tarjeta de débito', '28', 70, ['requiere_autorizacion', 'requiere_terminal', 'requiere_ultimos_4_digitos']],
        'TARJETA_SERVICIOS' => ['Tarjeta de servicios', '29', 80, ['requiere_autorizacion']],
        'APLICACION_ANTICIPO' => ['Aplicación de anticipo', '30', 90, ['requiere_anticipo_relacionado']],
        'INTERMEDIARIO_PAGOS' => ['Intermediario de pagos', null, 100, ['requiere_proveedor', 'requiere_referencia', 'requiere_forma_pago_sat']],
        'POR_DEFINIR' => ['Por definir', '99', 110, []],
        'DEPOSITO_BANCARIO' => ['Depósito bancario', null, 120, ['requiere_banco', 'requiere_referencia', 'requiere_comprobante', 'requiere_forma_pago_sat']],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->migration()->up();
    }

    public function test_first_run_creates_exactly_the_configured_catalog(): void
    {
        $this->seed(MetodoPagoSeeder::class);
        $this->assertSame(array_keys(self::METODOS), MetodoPago::ordenados()->pluck('clave')->all());
        $this->assertSame(12, MetodoPago::count());

        foreach (self::METODOS as $clave => [$nombre, $sat, $orden, $trueFields]) {
            $metodo = MetodoPago::where('clave', $clave)->firstOrFail();
            $this->assertSame($nombre, $metodo->nombre);
            $this->assertSame($sat, $metodo->clave_forma_pago_sat);
            $this->assertSame($orden, $metodo->orden);
            $this->assertTrue($metodo->activo);
            foreach (array_keys(array_filter($metodo->getCasts(), fn ($cast) => $cast === 'boolean')) as $campo) {
                if ($campo !== 'activo') {
                    $this->assertSame(in_array($campo, $trueFields, true), $metodo->{$campo}, $clave.' '.$campo);
                }
            }
            if ($sat !== null) {
                $this->assertSame(2, strlen($metodo->clave_forma_pago_sat));
            }
        }
    }

    public function test_repeated_run_is_idempotent_and_independent_of_numeric_ids(): void
    {
        $temporary = MetodoPago::create(['clave' => 'TEMPORAL', 'nombre' => 'Temporal']);
        $temporary->delete();
        $this->seed(MetodoPagoSeeder::class);
        $ids = MetodoPago::pluck('metodo_pago_id', 'clave')->all();
        $this->seed(MetodoPagoSeeder::class);

        $this->assertSame(12, MetodoPago::count());
        $this->assertSame($ids, MetodoPago::pluck('metodo_pago_id', 'clave')->all());
        $this->assertGreaterThan(1, MetodoPago::where('clave', MetodoPago::EFECTIVO)->value('metodo_pago_id'));
    }

    public function test_repeated_run_preserves_administrative_changes_and_custom_methods(): void
    {
        $this->seed(MetodoPagoSeeder::class);
        MetodoPago::where('clave', MetodoPago::TRANSFERENCIA_SPEI)->update([
            'nombre' => 'Transferencia configurada', 'descripcion' => 'Manual',
            'clave_forma_pago_sat' => '99', 'requiere_banco' => false,
            'requiere_terminal' => true, 'activo' => false, 'orden' => 777,
        ]);
        $custom = MetodoPago::create([
            'clave' => 'CRIPTOMONEDA_USUARIO', 'nombre' => 'Personalizado',
            'descripcion' => 'Intocable', 'activo' => false, 'orden' => 3,
        ])->fresh();
        $this->seed(MetodoPagoSeeder::class);

        $configured = MetodoPago::where('clave', MetodoPago::TRANSFERENCIA_SPEI)->firstOrFail();
        $this->assertSame('Transferencia configurada', $configured->nombre);
        $this->assertSame('Manual', $configured->descripcion);
        $this->assertSame('99', $configured->clave_forma_pago_sat);
        $this->assertFalse($configured->requiere_banco);
        $this->assertTrue($configured->requiere_terminal);
        $this->assertFalse($configured->activo);
        $this->assertSame(777, $configured->orden);
        $this->assertSame($custom->getAttributes(), $custom->fresh()->getAttributes());
    }

    public function test_missing_initial_method_is_recreated_without_touching_custom_methods(): void
    {
        $this->seed(MetodoPagoSeeder::class);
        MetodoPago::where('clave', MetodoPago::EFECTIVO)->delete();
        $custom = MetodoPago::create(['clave' => 'PERSONALIZADO', 'nombre' => 'Personalizado', 'orden' => 4])->fresh();
        $this->seed(MetodoPagoSeeder::class);

        $this->assertSame(13, MetodoPago::count());
        $this->assertDatabaseHas('metodos_pago', ['clave' => MetodoPago::EFECTIVO, 'nombre' => 'Efectivo']);
        $this->assertSame($custom->getAttributes(), $custom->fresh()->getAttributes());
    }

    private function migration()
    {
        return require database_path('migrations/2026_09_08_000001_create_metodos_pago_table.php');
    }
}
