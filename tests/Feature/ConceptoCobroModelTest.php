<?php

namespace Tests\Feature;

use App\Models\ConceptoCobro;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConceptoCobroModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->migration()->up();
    }

    public function test_model_configuration_and_fillable_fields(): void
    {
        $model = new ConceptoCobro();
        $expected = [
            'clave', 'nombre', 'descripcion', 'clave_producto_servicio_sat',
            'clave_unidad_sat', 'objeto_impuesto_sat', 'tasa_iva', 'activo', 'orden',
        ];

        $this->assertSame('conceptos_cobro', $model->getTable());
        $this->assertSame('concepto_cobro_id', $model->getKeyName());
        $this->assertSame($expected, $model->getFillable());
        $this->assertNotContains('concepto_cobro_id', $model->getFillable());
        $this->assertNotContains('created_at', $model->getFillable());
        $this->assertNotContains('updated_at', $model->getFillable());
    }

    public function test_model_casts_values_to_expected_types(): void
    {
        $concepto = ConceptoCobro::create([
            'clave' => 'CAST', 'nombre' => 'Cast', 'activo' => 1, 'orden' => '12', 'tasa_iva' => '0.160000',
        ])->fresh();

        $this->assertIsBool($concepto->activo);
        $this->assertTrue($concepto->activo);
        $this->assertIsInt($concepto->orden);
        $this->assertSame(12, $concepto->orden);
        $this->assertIsString($concepto->tasa_iva);
        $this->assertSame('0.160000', $concepto->tasa_iva);
    }

    public function test_scopes_filter_active_records_and_order_by_order_then_name(): void
    {
        ConceptoCobro::create(['clave' => 'B', 'nombre' => 'Beta', 'activo' => true, 'orden' => 20]);
        ConceptoCobro::create(['clave' => 'Z', 'nombre' => 'Zeta', 'activo' => false, 'orden' => 5]);
        ConceptoCobro::create(['clave' => 'C', 'nombre' => 'Charlie', 'activo' => true, 'orden' => 10]);
        ConceptoCobro::create(['clave' => 'A', 'nombre' => 'Alfa', 'activo' => true, 'orden' => 20]);

        $this->assertSame(
            ['C', 'A', 'B'],
            ConceptoCobro::activos()->ordenados()->pluck('clave')->all()
        );
    }

    private function migration()
    {
        return require database_path('migrations/2026_08_17_000001_create_conceptos_cobro_table.php');
    }
}
