<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetodoPagoModelTest extends TestCase
{
    private const BOOLEANOS = [
        'requiere_forma_pago_sat', 'requiere_banco', 'requiere_referencia',
        'requiere_numero_cheque', 'requiere_rastreo_spei', 'requiere_autorizacion',
        'requiere_terminal', 'requiere_ultimos_4_digitos', 'requiere_proveedor',
        'requiere_anticipo_relacionado', 'requiere_comprobante', 'activo',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->migration()->up();
    }

    public function test_model_configuration_fillable_casts_and_constants(): void
    {
        $model = new MetodoPago();
        $fillable = array_merge(['clave', 'nombre', 'descripcion', 'clave_forma_pago_sat'], self::BOOLEANOS, ['orden']);
        $constants = [
            'EFECTIVO', 'CHEQUE_NOMINATIVO', 'TRANSFERENCIA_SPEI', 'TARJETA_CREDITO',
            'MONEDERO_ELECTRONICO', 'DINERO_ELECTRONICO', 'TARJETA_DEBITO',
            'TARJETA_SERVICIOS', 'APLICACION_ANTICIPO', 'INTERMEDIARIO_PAGOS',
            'POR_DEFINIR', 'DEPOSITO_BANCARIO',
        ];

        $this->assertSame('metodos_pago', $model->getTable());
        $this->assertSame('metodo_pago_id', $model->getKeyName());
        $this->assertSame($fillable, $model->getFillable());
        foreach (self::BOOLEANOS as $campo) {
            $this->assertSame('boolean', $model->getCasts()[$campo]);
        }
        $this->assertSame('integer', $model->getCasts()['orden']);
        foreach ($constants as $constant) {
            $this->assertSame($constant, constant(MetodoPago::class.'::'.$constant));
        }
    }

    public function test_casts_scopes_and_sat_key_with_leading_zero(): void
    {
        MetodoPago::create(['clave' => 'B', 'nombre' => 'Beta', 'activo' => true, 'orden' => '20']);
        MetodoPago::create(['clave' => 'Z', 'nombre' => 'Zeta', 'activo' => false, 'orden' => 5]);
        MetodoPago::create(['clave' => 'C', 'nombre' => 'Charlie', 'activo' => true, 'orden' => 10]);
        $metodo = MetodoPago::create([
            'clave' => 'A', 'nombre' => 'Alfa', 'clave_forma_pago_sat' => '01',
            'activo' => 1, 'orden' => '20', 'requiere_banco' => 1,
        ])->fresh();

        $this->assertSame(['C', 'A', 'B'], MetodoPago::activos()->ordenados()->pluck('clave')->all());
        $this->assertSame('01', $metodo->clave_forma_pago_sat);
        $this->assertIsBool($metodo->requiere_banco);
        $this->assertTrue($metodo->requiere_banco);
        $this->assertIsInt($metodo->orden);
    }

    private function migration()
    {
        return require database_path('migrations/2026_09_08_000001_create_metodos_pago_table.php');
    }
}
