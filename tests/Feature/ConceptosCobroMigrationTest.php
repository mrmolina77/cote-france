<?php

namespace Tests\Feature;

use App\Models\ConceptoCobro;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConceptosCobroMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
    }

    public function test_migration_creates_expected_structure_defaults_and_nullable_fiscal_fields(): void
    {
        $this->migration()->up();

        $this->assertTrue(Schema::hasTable('conceptos_cobro'));
        $this->assertTrue(Schema::hasColumns('conceptos_cobro', [
            'concepto_cobro_id', 'clave', 'nombre', 'descripcion',
            'clave_producto_servicio_sat', 'clave_unidad_sat', 'objeto_impuesto_sat',
            'tasa_iva', 'activo', 'orden', 'created_at', 'updated_at',
        ]));

        DB::table('conceptos_cobro')->insert(['clave' => 'PRUEBA', 'nombre' => 'Prueba']);
        $concepto = ConceptoCobro::where('clave', 'PRUEBA')->firstOrFail();
        $this->assertTrue($concepto->activo);
        $this->assertSame(0, $concepto->orden);
        $this->assertNull($concepto->descripcion);
        $this->assertNull($concepto->clave_producto_servicio_sat);
        $this->assertNull($concepto->clave_unidad_sat);
        $this->assertNull($concepto->objeto_impuesto_sat);
        $this->assertNull($concepto->tasa_iva);
    }

    public function test_required_and_unique_constraints_reject_invalid_records(): void
    {
        $this->migration()->up();
        DB::table('conceptos_cobro')->insert(['clave' => 'UNICA', 'nombre' => 'Válido']);

        foreach ([
            ['nombre' => 'Sin clave'],
            ['clave' => 'SIN_NOMBRE'],
            ['clave' => 'UNICA', 'nombre' => 'Duplicado'],
        ] as $invalid) {
            try {
                DB::table('conceptos_cobro')->insert($invalid);
                $this->fail('La restricción estructural debió rechazar el registro.');
            } catch (QueryException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    public function test_decimal_rate_is_declared_with_six_decimals_and_cast_without_float(): void
    {
        $this->migration()->up();
        $column = collect(DB::select("PRAGMA table_info('conceptos_cobro')"))->firstWhere('name', 'tasa_iva');
        $this->assertSame('decimal(8, 6)', strtolower($column->type));

        ConceptoCobro::create(['clave' => 'IVA_TEMPORAL', 'nombre' => 'IVA temporal', 'tasa_iva' => '0.160000']);
        $rate = ConceptoCobro::where('clave', 'IVA_TEMPORAL')->firstOrFail()->tasa_iva;
        $this->assertIsString($rate);
        $this->assertSame('0.160000', $rate);
    }

    public function test_rollback_only_removes_conceptos_cobro(): void
    {
        Schema::create('tabla_ajena', function (Blueprint $table) {
            $table->id();
        });
        $migration = $this->migration();
        $migration->up();

        $migration->down();

        $this->assertFalse(Schema::hasTable('conceptos_cobro'));
        $this->assertTrue(Schema::hasTable('tabla_ajena'));
    }

    private function migration()
    {
        return require database_path('migrations/2026_08_17_000001_create_conceptos_cobro_table.php');
    }
}
