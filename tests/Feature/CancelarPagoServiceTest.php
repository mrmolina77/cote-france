<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\PagoAplicacion;
use App\Models\ResponsablePago;
use App\Services\Facturacion\AplicarPagoService;
use App\Services\Facturacion\CancelarPagoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelarPagoServiceTest extends InscripcionesTestCase
{
    private $inscripcion;
    private $usuario;
    private $metodo;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-10 12:00:00');
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $responsable = ResponsablePago::create([
            'tipo' => 'persona', 'prospectos_id' => $prospecto->getKey(),
            'nombre_razon_social' => 'Responsable', 'activo' => true,
        ]);
        $this->inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $this->inscripcion->update([
            'estatus' => 'activa', 'moneda' => 'MXN', 'responsable_pago_id' => $responsable->getKey(),
        ]);
        $this->usuario = $this->user('admin');
        $this->metodo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cancels_confirmed_payment_preserving_audit_and_restoring_multiple_charge_states(): void
    {
        $future = $this->cargo('20.00', ['fecha_vencimiento' => '2026-09-30']);
        $overdue = $this->cargo('10.00', ['fecha_vencimiento' => '2026-09-09']);
        $pago = $this->confirm('15.00', [$future->getKey() => '5.00', $overdue->getKey() => '10.00']);
        $folio = $pago->folio;
        $confirmedBy = $pago->confirmed_by;
        $confirmedAt = $pago->fecha_confirmacion->toDateTimeString();
        $applications = $pago->aplicaciones->map->only(['pago_aplicacion_id', 'importe_aplicado', 'saldo_anterior', 'saldo_posterior'])->all();

        $cancelado = app(CancelarPagoService::class)->cancelar($pago->getKey(), '  Error bancario  ', $this->usuario->getKey());

        $this->assertSame(Pago::ESTADO_CANCELADO, $cancelado->estado);
        $this->assertSame('Error bancario', $cancelado->motivo_cancelacion);
        $this->assertSame($this->usuario->getKey(), (int) $cancelado->cancelled_by);
        $this->assertTrue($cancelado->fecha_cancelacion->equalTo(now()));
        $this->assertSame($confirmedBy, $cancelado->confirmed_by);
        $this->assertSame($confirmedAt, $cancelado->fecha_confirmacion->toDateTimeString());
        $this->assertSame($folio, $cancelado->folio);
        $this->assertSame(['20.00', Cargo::ESTADO_PENDIENTE], [$future->fresh()->saldo_pendiente, $future->fresh()->estado]);
        $this->assertSame(['10.00', Cargo::ESTADO_VENCIDO], [$overdue->fresh()->saldo_pendiente, $overdue->fresh()->estado]);
        $this->assertSame($applications, $cancelado->aplicaciones->map->only(['pago_aplicacion_id', 'importe_aplicado', 'saldo_anterior', 'saldo_posterior'])->all());
        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseCount('pago_aplicaciones', 2);
    }

    public function test_restores_current_balance_when_a_later_confirmed_payment_exists(): void
    {
        $cargo = $this->cargo('100.00');
        $first = $this->confirm('30.00', [$cargo->getKey() => '30.00']);
        $second = $this->confirm('20.00', [$cargo->getKey() => '20.00']);

        app(CancelarPagoService::class)->cancelar($first->getKey(), 'Pago equivocado', $this->usuario->getKey());

        $this->assertSame('80.00', $cargo->fresh()->saldo_pendiente);
        $this->assertSame(Cargo::ESTADO_PARCIAL, $cargo->fresh()->estado);
        $this->assertSame(Pago::ESTADO_CONFIRMADO, $second->fresh()->estado);
        $this->assertSame('100.00', $first->aplicaciones->first()->saldo_anterior);
    }

    /** @dataProvider invalidReasons */
    public function test_rejects_invalid_reason_without_changes(string $reason): void
    {
        $cargo = $this->cargo('10.00');
        $pago = $this->confirm('5.00', [$cargo->getKey() => '5.00']);

        try {
            app(CancelarPagoService::class)->cancelar($pago->getKey(), $reason, $this->usuario->getKey());
            $this->fail('El motivo inválido debió rechazarse.');
        } catch (ValidationException $exception) {
            $this->assertSame('5.00', $cargo->fresh()->saldo_pendiente);
            $this->assertSame(Pago::ESTADO_CONFIRMADO, $pago->fresh()->estado);
        }
    }

    public function invalidReasons(): array
    {
        return [[''], ['   '], [str_repeat('x', 2001)]];
    }

    /** @dataProvider invalidStates */
    public function test_rejects_missing_or_non_confirmed_payment($id, ?string $state): void
    {
        if ($state !== null) {
            $pago = Pago::forceCreate($this->paymentData($state));
            $id = $pago->getKey();
        }
        $this->expectException(ValidationException::class);
        app(CancelarPagoService::class)->cancelar($id, 'Motivo válido', $this->usuario->getKey());
    }

    public function invalidStates(): array
    {
        return [[999999, null], [0, Pago::ESTADO_BORRADOR], [0, Pago::ESTADO_CANCELADO], [0, Pago::ESTADO_REEMBOLSADO]];
    }

    public function test_second_cancellation_does_not_restore_twice(): void
    {
        $cargo = $this->cargo('10.00');
        $pago = $this->confirm('4.00', [$cargo->getKey() => '4.00']);
        $service = app(CancelarPagoService::class);
        $service->cancelar($pago->getKey(), 'Primera cancelación', $this->usuario->getKey());

        try {
            $service->cancelar($pago->getKey(), 'Segunda cancelación', $this->usuario->getKey());
            $this->fail('La segunda cancelación debió rechazarse.');
        } catch (ValidationException $exception) {
            $this->assertSame('10.00', $cargo->fresh()->saldo_pendiente);
            $this->assertDatabaseCount('pago_aplicaciones', 1);
        }
    }

    public function test_inconsistent_balance_and_cancelled_charge_roll_back_every_charge_and_payment(): void
    {
        $valid = $this->cargo('10.00');
        $invalid = $this->cargo('10.00');
        $pago = $this->confirm('10.00', [$valid->getKey() => '5.00', $invalid->getKey() => '5.00']);
        $invalid->forceFill(['saldo_pendiente' => '9.00'])->save();

        $this->assertCancellationFails($pago, $valid, '5.00');

        $invalid->forceFill(['saldo_pendiente' => '5.00', 'estado' => Cargo::ESTADO_CANCELADO])->save();
        $this->assertCancellationFails($pago, $valid, '5.00');
        $this->assertDatabaseCount('pago_aplicaciones', 2);
    }

    public function test_cancelled_folio_stays_reserved_and_counter_advances(): void
    {
        $firstCargo = $this->cargo('1.00');
        $first = $this->confirm('1.00', [$firstCargo->getKey() => '1.00']);
        app(CancelarPagoService::class)->cancelar($first->getKey(), 'Cancelación', $this->usuario->getKey());
        $counter = DB::table('consecutivos_pago')->value('ultimo_consecutivo');
        $secondCargo = $this->cargo('1.00');
        $second = $this->confirm('1.00', [$secondCargo->getKey() => '1.00']);

        $this->assertNotSame($first->folio, $second->folio);
        $this->assertSame($first->folio, $first->fresh()->folio);
        $this->assertGreaterThan($counter, DB::table('consecutivos_pago')->value('ultimo_consecutivo'));
        $this->expectException(\Illuminate\Database\QueryException::class);
        Pago::forceCreate($this->paymentData(Pago::ESTADO_BORRADOR, ['folio' => $first->folio]));
    }

    private function assertCancellationFails(Pago $pago, Cargo $valid, string $expectedBalance): void
    {
        try {
            app(CancelarPagoService::class)->cancelar($pago->getKey(), 'Motivo', $this->usuario->getKey());
            $this->fail('La restauración insegura debió rechazarse.');
        } catch (ValidationException $exception) {
            $this->assertSame($expectedBalance, $valid->fresh()->saldo_pendiente);
            $this->assertSame(Pago::ESTADO_CONFIRMADO, $pago->fresh()->estado);
        }
    }

    private function cargo(string $total, array $overrides = []): Cargo
    {
        return Cargo::create(array_merge([
            'inscripciones_id' => $this->inscripcion->getKey(), 'concepto_cobro_id' => 1,
            'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-30',
            'moneda' => 'MXN', 'subtotal' => $total, 'total' => $total, 'saldo_pendiente' => $total,
            'estado' => Cargo::ESTADO_PENDIENTE, 'origen' => Cargo::ORIGEN_MANUAL,
        ], $overrides));
    }

    private function confirm(string $amount, array $applications): Pago
    {
        return app(AplicarPagoService::class)->confirmar(
            $this->inscripcion->getKey(), $this->metodo->getKey(),
            ['fecha_pago' => '2026-09-10 10:00:00', 'zona_horaria' => 'UTC', 'monto' => $amount],
            $applications, $this->usuario->getKey()
        );
    }

    private function paymentData(string $state, array $overrides = []): array
    {
        return array_merge([
            'folio' => uniqid('PAG-2026-'), 'inscripciones_id' => $this->inscripcion->getKey(),
            'prospectos_id' => $this->inscripcion->prospectos_id,
            'responsable_pago_id' => $this->inscripcion->responsable_pago_id,
            'fecha_pago' => now(), 'zona_horaria' => 'UTC', 'moneda' => 'MXN', 'monto' => '1.00',
            'metodo_pago_id' => $this->metodo->getKey(), 'estado' => $state,
        ], $overrides);
    }
}
