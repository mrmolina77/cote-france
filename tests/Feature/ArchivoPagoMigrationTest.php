<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ArchivoPagoMigrationTest extends PagosTestCase
{
    private $migration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migration = require database_path('migrations/2026_09_11_000001_create_archivos_pago_table.php');
        $this->migration->up();
        Storage::fake('local');
    }

    public function test_columnas_clave_primaria_foraneas_e_indices(): void
    {
        $this->assertSame([
            'archivo_pago_id', 'pago_id', 'nombre_original', 'disco', 'ruta', 'mime_type',
            'tamano_bytes', 'created_by', 'created_at', 'updated_at',
        ], Schema::getColumnListing('archivos_pago'));

        $columnas = collect(DB::select("PRAGMA table_info('archivos_pago')"))->keyBy('name');
        $this->assertSame(1, (int) $columnas['archivo_pago_id']->pk);
        $this->assertSame(0, (int) $columnas['created_by']->notnull);
        $fks = collect(DB::select("PRAGMA foreign_key_list('archivos_pago')"));
        $this->assertTrue($fks->contains(fn ($fk) => $fk->from === 'pago_id' && $fk->table === 'pagos' && strtoupper($fk->on_delete) === 'RESTRICT'));
        $this->assertTrue($fks->contains(fn ($fk) => $fk->from === 'created_by' && $fk->table === 'users' && strtoupper($fk->on_delete) === 'SET NULL'));
        $indices = collect(DB::select("PRAGMA index_list('archivos_pago')"));
        $this->assertTrue($indices->contains(fn ($i) => str_contains($i->name, 'pago_id_created_at')));
        $this->assertTrue($indices->contains(fn ($i) => (int) $i->unique === 1 && str_contains($i->name, 'disco_ruta')));
    }

    public function test_ubicacion_es_unica_usuario_es_nullable_y_pago_con_evidencia_no_se_borra(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');
        $user = DB::table('users')->insertGetId([]);
        $pago = DB::table('pagos')->insertGetId($this->paymentAttributes());
        $id = DB::table('archivos_pago')->insertGetId($this->archivo($pago, $user));
        Storage::disk('local')->put('archivos_pago/evidencia.pdf', 'bytes');

        $this->expectException(QueryException::class);
        try {
            DB::table('archivos_pago')->insert($this->archivo($pago, $user));
        } finally {
            DB::table('users')->where('id', $user)->delete();
            $this->assertNull(DB::table('archivos_pago')->where('archivo_pago_id', $id)->value('created_by'));
            try { DB::table('pagos')->where('pago_id', $pago)->delete(); } catch (QueryException $e) {}
            $this->assertDatabaseHas('pagos', ['pago_id' => $pago]);
            $this->assertDatabaseHas('archivos_pago', ['archivo_pago_id' => $id]);
            Storage::disk('local')->assertExists('archivos_pago/evidencia.pdf');
        }
    }

    public function test_down_solo_elimina_metadatos_y_up_se_puede_reaplicar(): void
    {
        $pago = DB::table('pagos')->insertGetId($this->paymentAttributes());
        DB::table('archivos_pago')->insert($this->archivo($pago, null));
        Storage::disk('local')->put('archivos_pago/evidencia.pdf', 'bytes');

        $this->migration->down();

        $this->assertFalse(Schema::hasTable('archivos_pago'));
        $this->assertTrue(Schema::hasTable('pagos'));
        $this->assertDatabaseHas('pagos', ['pago_id' => $pago]);
        Storage::disk('local')->assertExists('archivos_pago/evidencia.pdf');
        $this->migration->up();
        $this->assertTrue(Schema::hasTable('archivos_pago'));
        $this->assertDatabaseCount('archivos_pago', 0);
    }

    private function archivo(int $pago, ?int $user): array
    {
        return ['pago_id' => $pago, 'nombre_original' => 'evidencia.pdf', 'disco' => 'local',
            'ruta' => 'archivos_pago/evidencia.pdf', 'mime_type' => 'application/pdf',
            'tamano_bytes' => 5, 'created_by' => $user, 'created_at' => now(), 'updated_at' => now()];
    }
}
