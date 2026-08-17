<?php

namespace Tests\Feature;

use App\Models\ConceptoCobro;
use Database\Seeders\ConceptoCobroSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConceptoCobroSeederTest extends TestCase
{
    private const CONCEPTOS = [
        'INSCRIPCION' => ['Inscripción', 10],
        'MENSUALIDAD' => ['Mensualidad', 20],
        'MATERIAL' => ['Material', 30],
        'EXAMEN' => ['Examen', 40],
        'CURSO_ESPECIAL' => ['Curso especial', 50],
        'CLASE_INDIVIDUAL' => ['Clase individual', 60],
        'RECARGO' => ['Recargo', 70],
        'DESCUENTO' => ['Descuento', 80],
        'OTRO' => ['Otro', 90],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->migration()->up();
    }

    public function test_first_run_creates_exactly_the_nine_initial_concepts(): void
    {
        $this->seed(ConceptoCobroSeeder::class);

        $this->assertSame(array_keys(self::CONCEPTOS), ConceptoCobro::ordenados()->pluck('clave')->all());
        $this->assertSame(9, ConceptoCobro::count());
        foreach (self::CONCEPTOS as $clave => [$nombre, $orden]) {
            $concepto = ConceptoCobro::where('clave', $clave)->firstOrFail();
            $this->assertSame($nombre, $concepto->nombre);
            $this->assertSame($orden, $concepto->orden);
            $this->assertTrue($concepto->activo);
            $this->assertNull($concepto->clave_producto_servicio_sat);
            $this->assertNull($concepto->clave_unidad_sat);
            $this->assertNull($concepto->objeto_impuesto_sat);
            $this->assertNull($concepto->tasa_iva);
        }
    }

    public function test_repeated_run_is_idempotent_and_does_not_depend_on_numeric_ids(): void
    {
        ConceptoCobro::create(['clave' => 'PREEXISTENTE', 'nombre' => 'Temporal', 'orden' => 1]);
        ConceptoCobro::where('clave', 'PREEXISTENTE')->delete();

        $this->seed(ConceptoCobroSeeder::class);
        $ids = ConceptoCobro::pluck('concepto_cobro_id', 'clave')->all();
        $this->seed(ConceptoCobroSeeder::class);

        $this->assertSame(9, ConceptoCobro::count());
        $this->assertSame($ids, ConceptoCobro::pluck('concepto_cobro_id', 'clave')->all());
        $this->assertGreaterThan(1, ConceptoCobro::where('clave', 'INSCRIPCION')->value('concepto_cobro_id'));
    }

    public function test_repeated_run_preserves_manual_inactivation_and_fiscal_configuration(): void
    {
        $this->seed(ConceptoCobroSeeder::class);
        ConceptoCobro::where('clave', 'MENSUALIDAD')->update([
            'activo' => false,
            'clave_producto_servicio_sat' => 'CONFIGURADA',
            'clave_unidad_sat' => 'UNIDAD',
            'objeto_impuesto_sat' => 'OBJETO',
            'tasa_iva' => '0.160000',
        ]);

        $this->seed(ConceptoCobroSeeder::class);
        $concepto = ConceptoCobro::where('clave', 'MENSUALIDAD')->firstOrFail();

        $this->assertFalse($concepto->activo);
        $this->assertSame('CONFIGURADA', $concepto->clave_producto_servicio_sat);
        $this->assertSame('UNIDAD', $concepto->clave_unidad_sat);
        $this->assertSame('OBJETO', $concepto->objeto_impuesto_sat);
        $this->assertSame('0.160000', $concepto->tasa_iva);
        $this->assertSame(9, ConceptoCobro::count());
    }

    private function migration()
    {
        return require database_path('migrations/2026_08_17_000001_create_conceptos_cobro_table.php');
    }
}
