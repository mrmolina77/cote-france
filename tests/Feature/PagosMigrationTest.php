<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PagosMigrationTest extends PagosTestCase
{
    public function test_schema_columns_primary_key_types_defaults_and_indexes(): void
    {
        $columns = [
            'pago_id', 'folio', 'inscripciones_id', 'prospectos_id', 'responsable_pago_id',
            'fecha_pago', 'zona_horaria', 'moneda', 'tipo_cambio', 'monto', 'metodo_pago_id',
            'forma_pago_sat', 'banco', 'referencia', 'numero_cheque', 'rastreo_spei',
            'numero_autorizacion', 'terminal', 'ultimos_4_digitos', 'proveedor',
            'anticipo_relacionado_id', 'identificador_transaccion_externa', 'fecha_movimiento',
            'observaciones', 'estado', 'created_by', 'confirmed_by', 'fecha_confirmacion',
            'cancelled_by', 'fecha_cancelacion', 'motivo_cancelacion', 'fecha_reembolso',
            'created_at', 'updated_at',
        ];
        $this->assertTrue(Schema::hasTable('pagos'));
        $this->assertTrue(Schema::hasTable('consecutivos_pago'));
        $this->assertTrue(Schema::hasColumns('pagos', $columns));

        if (DB::getDriverName() === 'sqlite') {
            $columnsSqlite = collect(DB::select("PRAGMA table_info('pagos')"))->keyBy('name');
            foreach (['monto' => [12, 2], 'tipo_cambio' => [18, 6]] as $column => [$precision, $scale]) {
                $declaredType = $columnsSqlite[$column]->type;
                // DECIMAL/NUMERIC declarations must resolve to SQLite NUMERIC affinity.
                $this->assertMatchesRegularExpression('/(?:decimal|numeric)/i', $declaredType);
                if (preg_match('/\((\d+)\s*,\s*(\d+)\)/', $declaredType, $matches)) {
                    $this->assertSame($precision, (int) $matches[1]);
                    $this->assertSame($scale, (int) $matches[2]);
                }
            }
            $sql = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'pagos'")->sql;
            $this->assertStringContainsString('primary key autoincrement', strtolower($sql));
        } else {
            $numericColumns = collect(DB::select(
                'SELECT COLUMN_NAME, DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
                   FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME IN (?, ?)',
                [DB::getDatabaseName(), 'pagos', 'monto', 'tipo_cambio']
            ))->keyBy('COLUMN_NAME');
            foreach (['monto' => [12, 2], 'tipo_cambio' => [18, 6]] as $column => [$precision, $scale]) {
                $this->assertContains(strtolower($numericColumns[$column]->DATA_TYPE), ['decimal', 'numeric']);
                $this->assertSame($precision, (int) $numericColumns[$column]->NUMERIC_PRECISION);
                $this->assertSame($scale, (int) $numericColumns[$column]->NUMERIC_SCALE);
            }
        }

        $attributes = $this->paymentAttributes();
        DB::table('pagos')->insert($attributes);
        $pago = DB::table('pagos')->first();
        $this->assertSame('borrador', $pago->estado);
        $this->assertSame('MXN', $pago->moneda);
        $this->assertEquals('1.000000', $pago->tipo_cambio);

        $indexes = collect(DB::select("PRAGMA index_list('pagos')"));
        $this->assertTrue($indexes->contains(fn ($index) => (bool) $index->unique));
    }

    public function test_folio_is_unique_but_operational_references_are_not_globally_unique(): void
    {
        $base = $this->paymentAttributes([
            'folio' => 'PAG-2026-000001', 'referencia' => 'R', 'rastreo_spei' => 'S',
            'identificador_transaccion_externa' => 'E',
        ]);
        DB::table('pagos')->insert($base);
        DB::table('pagos')->insert($this->paymentAttributes([
            'folio' => 'PAG-2026-000002', 'referencia' => 'R', 'rastreo_spei' => 'S',
            'identificador_transaccion_externa' => 'E',
        ]));

        $this->expectException(QueryException::class);
        DB::table('pagos')->insert($this->paymentAttributes(['folio' => 'PAG-2026-000001']));
    }

    public function test_financial_foreign_keys_restrict_and_user_foreign_keys_set_null(): void
    {
        $user = DB::table('users')->insertGetId([]);
        $attributes = $this->paymentAttributes(['created_by' => $user, 'confirmed_by' => $user, 'cancelled_by' => $user]);
        $id = DB::table('pagos')->insertGetId($attributes);

        foreach ([
            ['inscripciones', 'inscripciones_id', $attributes['inscripciones_id']],
            ['prospectos', 'prospectos_id', $attributes['prospectos_id']],
            ['responsables_pago', 'responsable_pago_id', $attributes['responsable_pago_id']],
            ['metodos_pago', 'metodo_pago_id', $attributes['metodo_pago_id']],
        ] as [$table, $key, $parent]) {
            try {
                DB::table($table)->where($key, $parent)->delete();
                $this->fail("La FK de {$table} debe restringir la eliminación.");
            } catch (QueryException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }

        $child = DB::table('pagos')->insertGetId($this->paymentAttributes(['anticipo_relacionado_id' => $id]));
        try {
            DB::table('pagos')->where('pago_id', $id)->delete();
            $this->fail('La FK autorreferenciada debe restringir la eliminación.');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
        $this->assertNotNull($child);

        DB::table('users')->where('id', $user)->delete();
        $pago = DB::table('pagos')->where('pago_id', $id)->first();
        $this->assertNull($pago->created_by);
        $this->assertNull($pago->confirmed_by);
        $this->assertNull($pago->cancelled_by);
    }

    public function test_down_is_reversible_and_preserves_existing_financial_tables(): void
    {
        $this->pagosMigration()->down();
        $this->assertFalse(Schema::hasTable('pagos'));
        $this->assertFalse(Schema::hasTable('consecutivos_pago'));
        foreach (['inscripciones', 'prospectos', 'responsables_pago', 'metodos_pago', 'users'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
    }
}
