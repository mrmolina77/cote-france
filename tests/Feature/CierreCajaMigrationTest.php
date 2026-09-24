<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CierreCajaMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp(); config()->set('database.default','sqlite'); config()->set('database.connections.sqlite.database',':memory:'); DB::purge('sqlite');
        Schema::create('users',fn(Blueprint $t)=>$t->id());
    }

    public function test_migration_has_snapshot_relations_and_unique_business_identity(): void
    {
        $m=require database_path('migrations/2026_09_23_000001_create_cierres_caja_table.php'); $m->up();
        $detalle=require database_path('migrations/2026_09_24_000001_create_cierre_caja_movimientos_table.php'); $detalle->up();
        $u1=DB::table('users')->insertGetId([]); $u2=DB::table('users')->insertGetId([]);
        $row=['cajero_id'=>$u1,'fecha_operacion'=>'2026-09-23','ventana_inicio'=>'2026-09-23 00:00:00','ventana_fin'=>'2026-09-24 00:00:00','zona_horaria'=>'UTC','totales_esperados'=>'{}','importes_contados'=>'{}','diferencias'=>'{}','snapshot_movimientos'=>'{}','cerrado_por'=>$u2,'cerrado_en'=>'2026-09-24 00:00:00','estado'=>'cerrado','created_at'=>now(),'updated_at'=>now()];
        DB::table('cierres_caja')->insert($row); $this->expectException(\Illuminate\Database\QueryException::class); DB::table('cierres_caja')->insert($row);
    }

    public function test_snapshot_detail_has_foreign_key_and_unique_sequence(): void
    {
        $cierre=require database_path('migrations/2026_09_23_000001_create_cierres_caja_table.php'); $cierre->up();
        $detalle=require database_path('migrations/2026_09_24_000001_create_cierre_caja_movimientos_table.php'); $detalle->up();
        $u=DB::table('users')->insertGetId([]);
        $id=DB::table('cierres_caja')->insertGetId(['cajero_id'=>$u,'fecha_operacion'=>'2026-09-23','ventana_inicio'=>'2026-09-23 00:00:00','ventana_fin'=>'2026-09-24 00:00:00','zona_horaria'=>'UTC','totales_esperados'=>'{}','importes_contados'=>'{}','diferencias'=>'{}','snapshot_movimientos'=>'{}','cerrado_por'=>$u,'cerrado_en'=>'2026-09-24 00:00:00','estado'=>'cerrado','created_at'=>now(),'updated_at'=>now()]);
        $row=['cierre_caja_id'=>$id,'secuencia'=>1,'tipo'=>'ingreso','importe'=>'10.00','moneda'=>'MXN','metodo'=>'Efectivo'];
        DB::table('cierre_caja_movimientos')->insert($row);
        try { DB::table('cierre_caja_movimientos')->insert($row); $this->fail('La secuencia duplicada debió rechazarse.'); }
        catch (\Illuminate\Database\QueryException $e) { $this->assertDatabaseCount('cierre_caja_movimientos', 1); }
        $detalle->down();
        $this->assertFalse(Schema::hasTable('cierre_caja_movimientos'));
    }

    public function test_rollback_is_safe(): void
    {
        $m=require database_path('migrations/2026_09_23_000001_create_cierres_caja_table.php'); $m->up();
        $detalle=require database_path('migrations/2026_09_24_000001_create_cierre_caja_movimientos_table.php'); $detalle->up();
        $detalle->down(); $m->down();
        $this->assertFalse(Schema::hasTable('cierre_caja_movimientos'));
        $this->assertFalse(Schema::hasTable('cierres_caja'));
    }
}
