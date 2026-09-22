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
            ['token'=>'no', 'cvv'=>'123', 'aplicaciones'=>[['cargo_id'=>7, 'importe_aplicado'=>'100',
                'token'=>'anidado', 'detalle'=>['password'=>'no']]], 'operacion_atomica'=>true], 'not-an-ip', str_repeat('a', 700));
        $this->assertNull($evento->usuario_id);
        $this->assertSame('100.00', $evento->valores_nuevos['monto']);
        $this->assertSame('100.00', $evento->metadatos['aplicaciones'][0]['importe_aplicado']);
        $this->assertSame(['cargo_id', 'importe_aplicado'], array_keys($evento->metadatos['aplicaciones'][0]));
        $this->assertTrue($evento->metadatos['operacion_atomica']);
        $this->assertNull($evento->ip_address);
        $this->assertSame(500, mb_strlen($evento->user_agent));
        $this->assertArrayNotHasKey('observaciones', $evento->valores_nuevos);
        $this->assertArrayNotHasKey('token', $evento->metadatos);
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

    public function test_modification_contract_only_records_changed_allowed_fields(): void
    {
        $pago = Pago::create($this->paymentAttributes(['monto'=>'100.00']));
        $evento = app(AuditoriaPagoService::class)->registrarModificacion($pago,
            ['monto'=>'100.00', 'referencia'=>'A', 'password'=>'secreto'],
            ['monto'=>'125.50', 'referencia'=>'A', 'password'=>'nuevo']);

        $this->assertSame(['monto'=>'100.00'], $evento->valores_anteriores);
        $this->assertSame(['monto'=>'125.50'], $evento->valores_nuevos);
        $this->assertSame(['monto'], $evento->metadatos['campos_modificados']);
    }

    public function test_existing_audit_is_append_only(): void
    {
        $pago = Pago::create($this->paymentAttributes());
        $evento = app(AuditoriaPagoService::class)->registrar($pago, AuditoriaPago::CREAR);
        foreach (['accion'=>AuditoriaPago::CANCELAR, 'ip_address'=>'127.0.0.1', 'usuario_id'=>null,
            'valores_nuevos'=>['estado'=>'cancelado'], 'metadatos'=>['motivo'=>'x']] as $campo=>$valor) {
            try {
                $evento->forceFill([$campo=>$valor])->save();
                $this->fail("Se permitió modificar {$campo}.");
            } catch (\LogicException $e) {
                $this->assertSame('La auditoría financiera es inmutable.', $e->getMessage());
                $evento = $evento->fresh();
            }
        }
        $this->expectException(\LogicException::class);
        $evento->delete();
    }
}
