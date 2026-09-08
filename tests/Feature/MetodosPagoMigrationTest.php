<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MetodosPagoMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
    }

    public function test_migration_creates_expected_structure_defaults_and_index(): void
    {
        $this->migration()->up();

        $columns = [
            'metodo_pago_id', 'clave', 'nombre', 'descripcion', 'clave_forma_pago_sat',
            'requiere_forma_pago_sat', 'requiere_banco', 'requiere_referencia',
            'requiere_numero_cheque', 'requiere_rastreo_spei', 'requiere_autorizacion',
            'requiere_terminal', 'requiere_ultimos_4_digitos', 'requiere_proveedor',
            'requiere_anticipo_relacionado', 'requiere_comprobante', 'activo', 'orden',
            'created_at', 'updated_at',
        ];
        $this->assertTrue(Schema::hasTable('metodos_pago'));
        $this->assertTrue(Schema::hasColumns('metodos_pago', $columns));

        DB::table('metodos_pago')->insert(['clave' => 'PRUEBA', 'nombre' => 'Prueba']);
        $registro = (array) DB::table('metodos_pago')->where('clave', 'PRUEBA')->first();
        $this->assertGreaterThan(0, $registro['metodo_pago_id']);
        $this->assertNull($registro['descripcion']);
        $this->assertNull($registro['clave_forma_pago_sat']);
        foreach (array_filter($columns, fn ($column) => str_starts_with($column, 'requiere_')) as $booleano) {
            $this->assertSame(0, $registro[$booleano], $booleano);
        }
        $this->assertSame(1, $registro['activo']);
        $this->assertSame(0, $registro['orden']);

        $indexes = DB::select("PRAGMA index_list('metodos_pago')");
        $indexedColumns = collect($indexes)->map(function ($index) {
            return collect(DB::select("PRAGMA index_info('{$index->name}')"))->pluck('name')->all();
        });
        $this->assertTrue($indexedColumns->contains(['clave']));
        $this->assertTrue($indexedColumns->contains(['activo', 'orden']));
    }

    public function test_required_and_unique_constraints_are_enforced(): void
    {
        $this->migration()->up();
        DB::table('metodos_pago')->insert(['clave' => 'UNICA', 'nombre' => 'Válido']);

        foreach ([['nombre' => 'Sin clave'], ['clave' => 'SIN_NOMBRE'], ['clave' => 'UNICA', 'nombre' => 'Duplicado']] as $invalid) {
            try {
                DB::table('metodos_pago')->insert($invalid);
                $this->fail('La restricción estructural debió rechazar el registro.');
            } catch (QueryException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    public function test_rollback_only_removes_metodos_pago(): void
    {
        Schema::create('tabla_ajena', fn (Blueprint $table) => $table->id());
        $migration = $this->migration();
        $migration->up();
        $migration->down();

        $this->assertFalse(Schema::hasTable('metodos_pago'));
        $this->assertTrue(Schema::hasTable('tabla_ajena'));
    }

    private function migration()
    {
        return require database_path('migrations/2026_09_08_000001_create_metodos_pago_table.php');
    }
}
