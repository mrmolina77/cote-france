<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PagoAplicacionesMigrationTest extends InscripcionesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function test_schema_has_financial_columns_primary_key_indexes_and_decimal_precision(): void
    {
        $this->assertTrue(Schema::hasColumns('pago_aplicaciones', [
            'pago_aplicacion_id', 'pago_id', 'cargo_id', 'importe_aplicado',
            'saldo_anterior', 'saldo_posterior', 'created_at', 'updated_at',
        ]));
        $columns = collect(DB::select("PRAGMA table_info('pago_aplicaciones')"))->keyBy('name');
        $this->assertSame(1, (int) $columns['pago_aplicacion_id']->pk);
        foreach (['importe_aplicado', 'saldo_anterior', 'saldo_posterior'] as $column) {
            $this->assertMatchesRegularExpression('/decimal\s*\(\s*12\s*,\s*2\s*\)/i', $columns[$column]->type);
        }
        $indexes = collect(DB::select("PRAGMA index_list('pago_aplicaciones')"));
        $definitions = $indexes->mapWithKeys(function ($index) {
            $escapedName = str_replace("'", "''", $index->name);
            $columns = collect(DB::select("PRAGMA index_info('{$escapedName}')"))
                ->sortBy('seqno')->pluck('name')->all();

            return [$index->name => ['unique' => (bool) $index->unique, 'columns' => $columns]];
        });
        $this->assertTrue($definitions->contains(
            fn (array $index) => $index['unique'] && $index['columns'] === ['pago_id', 'cargo_id']
        ));
        $this->assertTrue($definitions->contains(
            fn (array $index) => ! $index['unique'] && $index['columns'] === ['cargo_id']
        ));
    }

    public function test_foreign_keys_restrict_and_payment_charge_pair_is_unique(): void
    {
        [$pago, $cargo] = $this->records();
        DB::table('pago_aplicaciones')->insert($this->application($pago, $cargo));

        try {
            DB::table('pagos')->where('pago_id', $pago)->delete();
            $this->fail('La eliminación del pago debió ser restringida.');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
        try {
            DB::table('cargos')->where('cargo_id', $cargo)->delete();
            $this->fail('La eliminación del cargo debió ser restringida.');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }

        $this->expectException(QueryException::class);
        DB::table('pago_aplicaciones')->insert($this->application($pago, $cargo));
    }

    public function test_down_only_removes_applications_and_is_reversible(): void
    {
        $migration = require database_path('migrations/2026_09_10_000001_create_pago_aplicaciones_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('pago_aplicaciones'));
        foreach (['pagos', 'cargos', 'inscripciones', 'prospectos', 'metodos_pago', 'conceptos_cobro'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $migration->up();
        $this->assertTrue(Schema::hasTable('pago_aplicaciones'));
    }

    private function records(): array
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $responsable = DB::table('responsables_pago')->insertGetId(['tipo' => 'persona', 'nombre_razon_social' => 'R', 'activo' => 1]);
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $inscripcion->update(['responsable_pago_id' => $responsable]);
        $cargo = DB::table('cargos')->insertGetId([
            'inscripciones_id' => $inscripcion->getKey(), 'concepto_cobro_id' => 1,
            'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-30',
            'total' => '10.00', 'saldo_pendiente' => '10.00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $metodo = DB::table('metodos_pago')->value('metodo_pago_id');
        $pago = DB::table('pagos')->insertGetId([
            'folio' => 'PAG-2026-000001', 'inscripciones_id' => $inscripcion->getKey(),
            'prospectos_id' => $prospecto->getKey(), 'responsable_pago_id' => $responsable,
            'fecha_pago' => now(), 'zona_horaria' => 'UTC', 'monto' => '10.00', 'metodo_pago_id' => $metodo,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$pago, $cargo];
    }

    private function application(int $pago, int $cargo): array
    {
        return ['pago_id' => $pago, 'cargo_id' => $cargo, 'importe_aplicado' => '10.00',
            'saldo_anterior' => '10.00', 'saldo_posterior' => '0.00', 'created_at' => now(), 'updated_at' => now()];
    }
}
