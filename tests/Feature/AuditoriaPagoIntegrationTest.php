<?php

namespace Tests\Feature;

use App\Models\AuditoriaPago;
use App\Models\Pago;
use App\Services\Facturacion\AuditoriaPagoService;

class AuditoriaPagoIntegrationTest extends PagosTestCase
{
    public function test_committed_financial_transaction_keeps_audit(): void
    {
        $pago = Pago::create($this->paymentAttributes(['estado'=>Pago::ESTADO_CONFIRMADO]));
        app(AuditoriaPagoService::class)->registrar($pago, AuditoriaPago::CONFIRMAR, null, [], ['estado'=>Pago::ESTADO_CONFIRMADO]);
        $this->assertDatabaseHas('auditoria_pagos', ['pago_id'=>$pago->getKey(), 'accion'=>AuditoriaPago::CONFIRMAR]);
    }
}
