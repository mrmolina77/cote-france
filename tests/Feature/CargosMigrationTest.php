<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CargosMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::statement('PRAGMA foreign_keys = ON');
        $this->createReferencedTables();
    }

    public function test_migration_creates_expected_columns_and_defaults(): void
    {
        $this->migration()->up();

        $this->assertTrue(Schema::hasColumns('cargos', [
            'cargo_id', 'inscripciones_id', 'concepto_cobro_id', 'periodo_anio', 'periodo_mes',
            'fecha_emision', 'fecha_vencimiento', 'moneda', 'subtotal', 'descuento', 'recargo',
            'impuestos', 'total', 'saldo_pendiente', 'estado', 'origen', 'clave_idempotencia',
            'observaciones', 'created_by', 'created_at', 'updated_at',
        ]));

        [$inscripcionId, $conceptoId] = $this->references();
        $id = DB::table('cargos')->insertGetId([
            'inscripciones_id' => $inscripcionId,
            'concepto_cobro_id' => $conceptoId,
            'fecha_emision' => '2026-09-01',
            'fecha_vencimiento' => '2026-09-10',
            'total' => '100.00',
            'saldo_pendiente' => '100.00',
        ]);
        $cargo = DB::table('cargos')->where('cargo_id', $id)->first();

        $this->assertSame('MXN', $cargo->moneda);
        $this->assertSame('pendiente', $cargo->estado);
        $this->assertSame('manual', $cargo->origen);
        $this->assertEquals(0, $cargo->subtotal);
        $this->assertEquals(0, $cargo->descuento);
        $this->assertEquals(0, $cargo->recargo);
        $this->assertEquals(0, $cargo->impuestos);
        $this->assertNull($cargo->periodo_anio);
        $this->assertNull($cargo->periodo_mes);
    }

    public function test_idempotency_key_is_unique_but_allows_multiple_nulls(): void
    {
        $this->migration()->up();
        [$inscripcionId, $conceptoId] = $this->references();
        $base = [
            'inscripciones_id' => $inscripcionId, 'concepto_cobro_id' => $conceptoId,
            'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-10',
            'total' => '10.00', 'saldo_pendiente' => '10.00',
        ];

        DB::table('cargos')->insert($base);
        DB::table('cargos')->insert($base);
        DB::table('cargos')->insert($base + ['clave_idempotencia' => 'MENSUALIDAD:1:2026-09']);

        $this->expectException(QueryException::class);
        DB::table('cargos')->insert($base + ['clave_idempotencia' => 'MENSUALIDAD:1:2026-09']);
    }

    public function test_foreign_keys_restrict_parents_and_null_deleted_creator(): void
    {
        $this->migration()->up();
        [$inscripcionId, $conceptoId, $userId] = $this->references(true);
        DB::table('cargos')->insert([
            'inscripciones_id' => $inscripcionId, 'concepto_cobro_id' => $conceptoId,
            'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-10',
            'total' => '10.00', 'saldo_pendiente' => '10.00', 'created_by' => $userId,
        ]);

        foreach ([['inscripciones', 'inscripciones_id', $inscripcionId], ['conceptos_cobro', 'concepto_cobro_id', $conceptoId]] as [$table, $key, $id]) {
            try {
                DB::table($table)->where($key, $id)->delete();
                $this->fail("La FK de {$table} debió restringir la eliminación.");
            } catch (QueryException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }

        DB::table('users')->where('id', $userId)->delete();
        $this->assertNull(DB::table('cargos')->value('created_by'));
    }

    public function test_rollback_only_removes_cargos(): void
    {
        Schema::create('tabla_ajena', fn (Blueprint $table) => $table->id());
        $migration = $this->migration();
        $migration->up();
        $migration->down();

        $this->assertFalse(Schema::hasTable('cargos'));
        $this->assertTrue(Schema::hasTable('tabla_ajena'));
        $this->assertTrue(Schema::hasTable('inscripciones'));
    }

    private function createReferencedTables(): void
    {
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('prospectos', fn (Blueprint $table) => $table->id('prospectos_id'));
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id('inscripciones_id');
            $table->unsignedBigInteger('prospectos_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('conceptos_cobro', function (Blueprint $table) {
            $table->id('concepto_cobro_id');
            $table->string('clave')->nullable();
            $table->string('nombre')->nullable();
            $table->timestamps();
        });
    }

    private function references(bool $withUser = false): array
    {
        $prospectoId = DB::table('prospectos')->insertGetId([]);
        $inscripcionId = DB::table('inscripciones')->insertGetId(['prospectos_id' => $prospectoId]);
        $conceptoId = DB::table('conceptos_cobro')->insertGetId([]);

        return $withUser
            ? [$inscripcionId, $conceptoId, DB::table('users')->insertGetId([])]
            : [$inscripcionId, $conceptoId];
    }

    private function migration()
    {
        return require database_path('migrations/2026_09_07_000001_create_cargos_table.php');
    }
}
