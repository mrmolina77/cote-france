<?php

namespace Tests\Feature;

use App\Models\AuditoriaPago;
use App\Models\Pago;
use App\Services\Facturacion\AuditoriaPagoService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AuditoriaPagoServiceTest extends PagosTestCase
{
    protected function setUp(): void { parent::setUp(); (require database_path('migrations/2026_09_22_000003_create_auditoria_pagos_table.php'))->up(); }

    public function test_records_sanitized_allow_list_data_and_system_events(): void
    {
        $pago = Pago::create($this->paymentAttributes(['monto'=>'100.00']));
        $service = app(AuditoriaPagoService::class);
        $evento = $service->registrar($pago, AuditoriaPago::CONFIRMAR, null, [], $service->snapshotPago($pago),
            ['token'=>'no', 'cvv'=>'123', 'detalle'=>['monto'=>'100.00']], null, null);
        $this->assertNull($evento->usuario_id);
        $this->assertSame('100.00', $evento->valores_nuevos['monto']);
        $this->assertSame(['detalle'=>['monto'=>'100.00']], $evento->metadatos);
        $this->assertArrayNotHasKey('observaciones', $evento->valores_nuevos);
    }

    public function test_invalid_action_and_transaction_rollback(): void
    {
        $pago = Pago::create($this->paymentAttributes());
        $service = app(AuditoriaPagoService::class);
        try {
            DB::transaction(function () use ($service, $pago) { $service->registrar($pago, AuditoriaPago::CREAR); throw new \RuntimeException('rollback'); });
        } catch (\RuntimeException $e) {}
        $this->assertDatabaseCount('auditoria_pagos', 0);
        $this->expectException(InvalidArgumentException::class);
        $service->registrar($pago, 'accion_invalida');
    }
}
