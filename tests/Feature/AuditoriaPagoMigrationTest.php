<?php

namespace Tests\Feature;

use App\Models\AuditoriaPago;
use App\Models\Pago;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

class AuditoriaPagoMigrationTest extends PagosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_schema_json_relations_and_append_only_model(): void
    {
        $this->assertTrue(Schema::hasColumns('auditoria_pagos', ['auditoria_pago_id','pago_id','usuario_id','accion','ip_address',
            'user_agent','ocurrido_en','valores_anteriores','valores_nuevos','metadatos','created_at','updated_at']));
        $pago = Pago::create($this->paymentAttributes());
        $evento = new AuditoriaPago();
        $evento->forceFill(['pago_id'=>$pago->getKey(), 'accion'=>AuditoriaPago::CONFIRMAR, 'ocurrido_en'=>now(),
            'valores_nuevos'=>['monto'=>'123.45']])->save();
        $this->assertSame(['monto'=>'123.45'], $evento->fresh()->valores_nuevos);
        $this->expectException(LogicException::class);
        $evento->delete();
    }

    public function test_foreign_key_rejects_orphan(): void
    {
        $this->expectException(QueryException::class);
        DB::table('auditoria_pagos')->insert(['pago_id'=>999999, 'accion'=>'confirmar', 'ocurrido_en'=>now(), 'created_at'=>now(), 'updated_at'=>now()]);
    }
}
