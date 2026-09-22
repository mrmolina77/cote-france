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
        $evento = $service->registrar($pago, AuditoriaPago::CONFIRMAR, null,
            ['token'=>'no', 'estado_previo'=>'no_existia'], $service->snapshotPago($pago),
            ['token'=>'no', 'aplicaciones'=>[['cargo_id'=>1, 'importe_aplicado'=>'100.00',
                'token'=>'anidado', 'detalle'=>['cvv'=>'123']]], 'arbitrario'=>'no'], 'no-es-ip', str_repeat('a', 700));
        $this->assertNull($evento->usuario_id);
        $this->assertSame('100.00', $evento->valores_nuevos['monto']);
        $this->assertSame(['estado_previo'=>'no_existia'], $evento->valores_anteriores);
        $this->assertSame(['aplicaciones'=>[['cargo_id'=>1, 'importe_aplicado'=>'100.00']]], $evento->metadatos);
        $this->assertArrayNotHasKey('observaciones', $evento->valores_nuevos);
        $this->assertNull($evento->ip_address);
        $this->assertSame(500, mb_strlen($evento->user_agent));
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

    public function test_modificar_contract_filters_to_payment_snapshots_and_changed_fields(): void
    {
        $pago = Pago::create($this->paymentAttributes(['monto'=>'100.00']));
        $evento = app(AuditoriaPagoService::class)->registrar($pago, AuditoriaPago::MODIFICAR, null,
            ['monto'=>'100.00', 'password'=>'secreto'], ['monto'=>'90.00', 'ruta_pdf'=>'privada'],
            ['campos_modificados'=>['monto', 'token'], 'token'=>'secreto']);
        $this->assertSame(['monto'=>'100.00'], $evento->valores_anteriores);
        $this->assertSame(['monto'=>'90.00'], $evento->valores_nuevos);
        $this->assertSame(['campos_modificados'=>['monto']], $evento->metadatos);
    }

    public function test_audit_model_is_append_only(): void
    {
        $pago = Pago::create($this->paymentAttributes());
        $evento = app(AuditoriaPagoService::class)->registrar($pago, AuditoriaPago::CREAR);
        foreach (['accion', 'valores_nuevos', 'metadatos', 'ip_address', 'usuario_id'] as $campo) {
            $copia = $evento->fresh();
            $copia->forceFill([$campo => $campo === 'usuario_id' ? null : 'alterado']);
            try { $copia->save(); $this->fail("Se permitió modificar {$campo}"); }
            catch (\LogicException $e) { $this->assertSame('La auditoría financiera es inmutable.', $e->getMessage()); }
        }
        $this->expectException(\LogicException::class);
        $evento->fresh()->delete();
    }
}
