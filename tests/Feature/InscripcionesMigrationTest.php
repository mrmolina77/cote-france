<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InscripcionesMigrationTest extends TestCase
{
    public function test_inscripciones_migration_is_reversible_in_isolated_sqlite_database(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        foreach (['prospectos' => 'prospectos_id', 'cursos' => 'cursos_id', 'grupos' => 'grupo_id'] as $table => $key) {
            Schema::create($table, function (Blueprint $blueprint) use ($key) {
                $blueprint->id($key);
            });
        }

        $migration = require database_path('migrations/2024_10_10_011507_create_inscripcions_table.php');
        $migration->up();
        $this->assertTrue(Schema::hasTable('inscripciones'));

        $migration->down();
        $this->assertFalse(Schema::hasTable('inscripciones'));
    }
}
