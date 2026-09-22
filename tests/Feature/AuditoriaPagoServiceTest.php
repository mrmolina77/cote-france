<?php

namespace Tests\Feature;

use App\Models\AuditoriaPago;
use App\Models\Pago;
use App\Services\Facturacion\AuditoriaPagoService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AuditoriaPagoServiceTest extends PagosTestCase
{
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

    public function test_valid_payload_is_kept_complete_with_decimal_scale_and_unknown_keys_are_removed(): void
    {
        $pago = Pago::create($this->paymentAttributes(['monto' => '123.40', 'tipo_cambio' => '17.123456']));

        $evento = app(AuditoriaPagoService::class)->registrar(
            $pago,
            AuditoriaPago::CONFIRMAR,
            null,
            [],
            ['monto' => '123.40', 'tipo_cambio' => '17.123456', 'desconocido' => 'secreto'],
            ['operacion_atomica' => true, 'aplicaciones' => [[
                'cargo_id' => 9,
                'importe_aplicado' => '123.40',
                'desconocido' => ['token' => 'no guardar'],
            ]]]
        );

        $this->assertSame('123.40', $evento->valores_nuevos['monto']);
        $this->assertSame('17.123456', $evento->valores_nuevos['tipo_cambio']);
        $this->assertArrayNotHasKey('desconocido', $evento->valores_nuevos);
        $this->assertSame([
            'cargo_id' => 9,
            'importe_aplicado' => '123.40',
        ], $evento->metadatos['aplicaciones'][0]);
    }

    public function test_oversized_payload_is_rejected_without_a_partial_audit(): void
    {
        $pago = Pago::create($this->paymentAttributes());
        $aplicaciones = [];
        for ($i = 1; $i <= 500; $i++) {
            $aplicaciones[] = [
                'cargo_id' => $i,
                'importe_aplicado' => '1.00',
                'saldo_anterior' => str_repeat((string) ($i % 10), 500),
                'saldo_posterior' => '0.00',
            ];
        }

        try {
            app(AuditoriaPagoService::class)->registrar(
                $pago,
                AuditoriaPago::CONFIRMAR,
                null,
                [],
                ['estado' => Pago::ESTADO_CONFIRMADO],
                ['operacion_atomica' => true, 'aplicaciones' => $aplicaciones]
            );
            $this->fail('Se aceptó un payload que excede el límite.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('excede el límite', $e->getMessage());
        }

        $this->assertDatabaseCount('auditoria_pagos', 0);
    }

    public function test_invalid_date_is_rejected_and_unexpected_scalar_array_is_not_persisted(): void
    {
        $pago = Pago::create($this->paymentAttributes());
        $service = app(AuditoriaPagoService::class);

        $evento = $service->registrar($pago, AuditoriaPago::CONFIRMAR, null, [], [
            'estado' => ['confirmado'],
            'monto' => ['100.00'],
        ]);
        $this->assertSame([], $evento->valores_nuevos);

        try {
            $service->registrar($pago, AuditoriaPago::CONFIRMAR, null, [], [
                'fecha_confirmacion' => '2026-99-99 25:61:61',
            ]);
            $this->fail('Se aceptó una fecha inválida.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('fecha_confirmacion', $e->getMessage());
        }
        $this->assertDatabaseCount('auditoria_pagos', 1);
    }

    public function test_save_update_force_fill_and_delete_leave_original_record_unchanged(): void
    {
        $pago = Pago::create($this->paymentAttributes());
        $evento = app(AuditoriaPagoService::class)->registrar(
            $pago,
            AuditoriaPago::CREAR,
            null,
            [],
            ['estado' => Pago::ESTADO_CONFIRMADO],
            [],
            '127.0.0.1'
        );
        $original = $evento->fresh()->getRawOriginal();

        foreach ([
            fn () => $evento->save(),
            fn () => $evento->update(['accion' => AuditoriaPago::CANCELAR]),
            fn () => $evento->forceFill(['ocurrido_en' => now()->addDay(), 'metadatos' => ['motivo' => 'x']])->save(),
            fn () => $evento->delete(),
        ] as $intento) {
            try {
                $intento();
                $this->fail('Se permitió mutar una auditoría existente.');
            } catch (\LogicException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
            $evento = $evento->fresh();
            $this->assertSame($original, $evento->getRawOriginal());
        }
    }
}
