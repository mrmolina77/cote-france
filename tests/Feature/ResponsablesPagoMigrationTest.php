<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResponsablesPagoMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::statement('PRAGMA foreign_keys = ON');

        Schema::create('prospectos', function (Blueprint $table) {
            $table->id('prospectos_id');
        });
    }

    public function test_migration_creates_expected_structure_defaults_and_constraints(): void
    {
        $migration = $this->migration();
        $migration->up();

        $this->assertTrue(Schema::hasColumns('responsables_pago', [
            'responsable_pago_id', 'tipo', 'prospectos_id', 'nombre_razon_social',
            'telefono', 'correo', 'activo', 'created_at', 'updated_at',
        ]));

        DB::table('prospectos')->insert(['prospectos_id' => 1]);
        DB::table('responsables_pago')->insert([
            'tipo' => 'persona',
            'prospectos_id' => 1,
            'nombre_razon_social' => 'Alumno responsable',
        ]);
        DB::table('responsables_pago')->insert([
            'tipo' => 'empresa',
            'nombre_razon_social' => 'Empresa externa',
        ]);

        $this->assertEquals(1, DB::table('responsables_pago')->where('prospectos_id', 1)->value('activo'));
        $this->assertNull(DB::table('responsables_pago')->where('tipo', 'empresa')->value('prospectos_id'));

        foreach ([
            ['nombre_razon_social' => 'Falta tipo'],
            ['tipo' => 'persona'],
            ['tipo' => 'persona', 'prospectos_id' => 999, 'nombre_razon_social' => 'Prospecto inexistente'],
        ] as $invalid) {
            try {
                DB::table('responsables_pago')->insert($invalid);
                $this->fail('La restricción estructural debió rechazar el registro.');
            } catch (QueryException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    public function test_migration_is_reversible_in_isolated_sqlite_database(): void
    {
        $migration = $this->migration();
        $migration->up();
        $this->assertTrue(Schema::hasTable('responsables_pago'));

        $migration->down();
        $this->assertFalse(Schema::hasTable('responsables_pago'));
        $this->assertTrue(Schema::hasTable('prospectos'));
    }

    private function migration()
    {
        return require database_path('migrations/2026_08_16_000001_create_responsables_pago_table.php');
    }
}
