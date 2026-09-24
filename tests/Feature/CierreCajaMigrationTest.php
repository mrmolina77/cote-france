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
        $u1=DB::table('users')->insertGetId([]); $u2=DB::table('users')->insertGetId([]);
        $row=['cajero_id'=>$u1,'fecha_operacion'=>'2026-09-23','ventana_inicio'=>'2026-09-23 00:00:00','ventana_fin'=>'2026-09-24 00:00:00','zona_horaria'=>'UTC','totales_esperados'=>'{}','importes_contados'=>'{}','diferencias'=>'{}','snapshot_movimientos'=>'{}','cerrado_por'=>$u2,'cerrado_en'=>'2026-09-24 00:00:00','estado'=>'cerrado','created_at'=>now(),'updated_at'=>now()];
        DB::table('cierres_caja')->insert($row); $this->expectException(\Illuminate\Database\QueryException::class); DB::table('cierres_caja')->insert($row);
    }

    public function test_rollback_is_safe(): void
    {
        $m=require database_path('migrations/2026_09_23_000001_create_cierres_caja_table.php'); $m->up(); $m->down(); $this->assertFalse(Schema::hasTable('cierres_caja'));
    }
}
