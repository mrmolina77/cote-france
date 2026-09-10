<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\PagoAplicacion;
use App\Models\ResponsablePago;
use App\Services\Facturacion\AplicarPagoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AplicarPagoServiceTest extends InscripcionesTestCase
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
        PagoAplicacion::flushEventListeners();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_complete_payment_persists_server_data_application_history_and_paid_state(): void
    {
        $cargo = $this->cargo('30.00');
        $pago = $this->confirm([
            'monto' => '30.00', 'inscripciones_id' => 999999, 'prospectos_id' => 999999,
            'responsable_pago_id' => 999999, 'metodo_pago_id' => 999999, 'moneda' => 'USD',
            'estado' => 'borrador', 'created_by' => 999999, 'confirmed_by' => 999999,
            'fecha_confirmacion' => '2000-01-01 00:00:00', 'forma_pago_sat' => '99',
        ], [$cargo->getKey() => '30.00'])->fresh();

        $this->assertSame($this->inscripcion->getKey(), (int) $pago->inscripciones_id);
        $this->assertSame((int) $this->inscripcion->prospectos_id, (int) $pago->prospectos_id);
        $this->assertSame((int) $this->inscripcion->responsable_pago_id, (int) $pago->responsable_pago_id);
        $this->assertSame($this->metodo->getKey(), (int) $pago->metodo_pago_id);
        $this->assertSame(Pago::ESTADO_CONFIRMADO, $pago->estado);
        $this->assertSame('MXN', $pago->moneda);
        $this->assertSame($this->usuario->getKey(), (int) $pago->created_by);
        $this->assertSame($this->usuario->getKey(), (int) $pago->confirmed_by);
        $this->assertTrue($pago->fecha_confirmacion->equalTo(now()));
        $this->assertSame($this->metodo->clave_forma_pago_sat, $pago->forma_pago_sat);
        $this->assertMatchesRegularExpression('/^PAG-2026-\d{6}$/', $pago->folio);
        $this->assertCount(1, $pago->aplicaciones);
        $this->assertSame('30.00', $pago->aplicaciones[0]->saldo_anterior);
        $this->assertSame('0.00', $pago->aplicaciones[0]->saldo_posterior);
        $this->assertSame('0.00', $cargo->fresh()->saldo_pendiente);
        $this->assertSame(Cargo::ESTADO_PAGADO, $cargo->fresh()->estado);
    }

    public function test_one_payment_can_fully_pay_one_charge_and_partially_pay_another(): void
    {
        $complete = $this->cargo('10.00');
        $partial = $this->cargo('20.00', ['fecha_vencimiento' => '2026-09-11']);

        $pago = $this->confirm(['monto' => '15.00'], [
            $complete->getKey() => '10.00',
            $partial->getKey() => '5.00',
        ]);
        $applications = $pago->aplicaciones->keyBy('cargo_id');

        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseCount('pago_aplicaciones', 2);
        $this->assertSame(['0.00', Cargo::ESTADO_PAGADO], [$complete->fresh()->saldo_pendiente, $complete->fresh()->estado]);
        $this->assertSame(['15.00', Cargo::ESTADO_PARCIAL], [$partial->fresh()->saldo_pendiente, $partial->fresh()->estado]);
        $this->assertSame(['10.00', '10.00', '0.00'], [
            $applications[$complete->getKey()]->importe_aplicado,
            $applications[$complete->getKey()]->saldo_anterior,
            $applications[$complete->getKey()]->saldo_posterior,
        ]);
        $this->assertSame(['5.00', '20.00', '15.00'], [
            $applications[$partial->getKey()]->importe_aplicado,
            $applications[$partial->getKey()]->saldo_anterior,
            $applications[$partial->getKey()]->saldo_posterior,
        ]);
        $this->assertSame('15.00', $pago->monto);
        $this->assertSame(1500, $applications->sum(function ($application) {
            [$units, $cents] = explode('.', $application->importe_aplicado);

            return ((int) $units * 100) + (int) $cents;
        }));
    }

    public function test_partial_and_overdue_payments_use_current_balance_and_correct_states(): void
    {
        $futuro = $this->cargo('20.00', ['fecha_vencimiento' => '2026-09-11']);
        $vencido = $this->cargo('20.00', ['fecha_vencimiento' => '2026-09-09']);
        $this->confirm(['monto' => '15.00'], [$futuro->getKey() => '10.00', $vencido->getKey() => '5.00']);

        $this->assertSame(['10.00', Cargo::ESTADO_PARCIAL], [$futuro->fresh()->saldo_pendiente, $futuro->fresh()->estado]);
        $this->assertSame(['15.00', Cargo::ESTADO_VENCIDO], [$vencido->fresh()->saldo_pendiente, $vencido->fresh()->estado]);
        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseCount('pago_aplicaciones', 2);
    }

    public function test_multiple_exact_decimal_applications_and_folios_are_not_reused(): void
    {
        $a = $this->cargo('0.10');
        $b = $this->cargo('0.20');
        $first = $this->confirm(['monto' => '0.30'], [$a->getKey() => '0.10', $b->getKey() => '0.20']);
        $this->metodo = MetodoPago::where('clave', MetodoPago::MONEDERO_ELECTRONICO)->firstOrFail();
        $c = $this->cargo('0.30');
        $second = $this->confirm(['monto' => '0.30', 'referencia' => 'duplicada', 'proveedor' => 'Proveedor'], [$c->getKey() => '0.30']);
        $d = $this->cargo('0.30');
        $third = $this->confirm(['monto' => '0.30', 'referencia' => 'duplicada', 'proveedor' => 'Proveedor'], [$d->getKey() => '0.30']);

        $this->assertNotSame($first->folio, $second->folio);
        $this->assertNotSame($second->folio, $third->folio);
        $this->assertDatabaseCount('pagos', 3);
    }

    /** @dataProvider invalidAmounts */
    public function test_rejects_invalid_amount_formats($amount): void
    {
        $cargo = $this->cargo('20.00');
        $this->expectException(ValidationException::class);
        $this->confirm(['monto' => '20.00'], [$cargo->getKey() => $amount]);
    }

    public function invalidAmounts(): array
    {
        return [[0], ['0.00'], ['-1.00'], ['1.001'], ['1e1'], [' 1.00'], [NAN], [INF], [0.1]];
    }

    public function test_rejects_total_above_below_balance_and_empty_applications(): void
    {
        foreach ([['11.00', '10.00'], ['9.00', '10.00'], ['21.00', '21.00']] as [$applied, $received]) {
            $cargo = $this->cargo('20.00');
            try {
                $this->confirm(['monto' => $received], [$cargo->getKey() => $applied]);
                $this->fail('El total inválido debió rechazarse.');
            } catch (ValidationException $exception) {
                $this->assertSame('20.00', $cargo->fresh()->saldo_pendiente);
            }
        }
        $this->expectException(ValidationException::class);
        $this->confirm(['monto' => '1.00'], []);
    }

    public function test_rejects_missing_foreign_cancelled_paid_zero_wrong_currency_and_duplicate_ids(): void
    {
        $cases = [
            [999999 => '1.00'],
            [$this->cargo('1.00', ['estado' => Cargo::ESTADO_CANCELADO])->getKey() => '1.00'],
            [$this->cargo('1.00', ['estado' => Cargo::ESTADO_PAGADO])->getKey() => '1.00'],
            [$this->cargo('0.00')->getKey() => '1.00'],
            [$this->cargo('1.00', ['moneda' => 'USD'])->getKey() => '1.00'],
        ];
        foreach ($cases as $applications) {
            try {
                $this->confirm(['monto' => '1.00'], $applications);
                $this->fail('El cargo inválido debió rechazarse.');
            } catch (ValidationException $exception) {
                $this->assertDatabaseCount('pagos', 0);
            }
        }
        $cargo = $this->cargo('2.00');
        $this->assertInvalidChargeIdentifiers($cargo, ['0'.$cargo->getKey() => '1.00', $cargo->getKey() => '1.00']);
    }

    /** @dataProvider invalidChargeIdentifiers */
    public function test_rejects_manipulated_charge_identifiers_without_financial_writes($identifier): void
    {
        $cargo = $this->cargo('10.00');
        $this->assertInvalidChargeIdentifiers($cargo, [$identifier => '1.00']);
    }

    public function invalidChargeIdentifiers(): array
    {
        return [['abc'], [0], [-1], ['1.5'], [' 1'], ['1e1']];
    }

    public function test_rejects_charge_from_other_enrollment_without_disclosing_it(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $other = $this->enroll($prospecto, $curso, $grupo);
        $cargo = $this->cargo('1.00', ['inscripciones_id' => $other->getKey()]);
        try {
            $this->confirm(['monto' => '1.00'], [$cargo->getKey() => '1.00']);
            $this->fail('Debió rechazarse.');
        } catch (ValidationException $exception) {
            $this->assertStringNotContainsString((string) $other->getKey(), $exception->getMessage());
        }
    }

    public function test_revalidates_persisted_state_and_rejects_inactive_method_responsible_enrollment_and_advance(): void
    {
        $cargo = $this->cargo('10.00');
        $cargo->update(['saldo_pendiente' => '5.00']);
        $this->assertRejected(fn () => $this->confirm(['monto' => '10.00'], [$cargo->getKey() => '10.00']));
        $this->assertDatabaseCount('pago_aplicaciones', 0);
        $this->assertDatabaseCount('consecutivos_pago', 0);
        $this->assertSame(['5.00', Cargo::ESTADO_PENDIENTE], [$cargo->fresh()->saldo_pendiente, $cargo->fresh()->estado]);

        $pago = $this->confirm(['monto' => '5.00'], [$cargo->getKey() => '5.00']);
        $this->assertSame('5.00', $pago->aplicaciones->first()->saldo_anterior);
        DB::table('pago_aplicaciones')->where('pago_id', $pago->getKey())->delete();
        DB::table('pagos')->where('pago_id', $pago->getKey())->delete();
        DB::table('consecutivos_pago')->delete();
        $cargo->forceFill(['saldo_pendiente' => '5.00', 'estado' => Cargo::ESTADO_PENDIENTE])->save();

        $this->metodo->update(['activo' => false]);
        $this->assertRejected(fn () => $this->confirm(['monto' => '5.00'], [$cargo->getKey() => '5.00']));
        $this->metodo->update(['activo' => true]);
        $this->inscripcion->responsablePago->update(['activo' => false]);
        $this->assertRejected(fn () => $this->confirm(['monto' => '5.00'], [$cargo->getKey() => '5.00']));
        $this->inscripcion->responsablePago->update(['activo' => true]);
        $this->inscripcion->update(['estatus' => 'cancelada']);
        $this->assertRejected(fn () => $this->confirm(['monto' => '5.00'], [$cargo->getKey() => '5.00']));
        $this->inscripcion->update(['estatus' => 'activa']);
        $this->metodo = MetodoPago::where('clave', MetodoPago::APLICACION_ANTICIPO)->firstOrFail();
        $this->assertRejected(fn () => $this->confirm(['monto' => '5.00', 'anticipo_relacionado_id' => 1], [$cargo->getKey() => '5.00']));
    }

    public function test_failure_after_first_application_rolls_back_payment_folio_and_all_balances(): void
    {
        $a = $this->cargo('10.00');
        $b = $this->cargo('10.00');
        $created = 0;
        PagoAplicacion::creating(function () use (&$created) {
            if (++$created === 2) {
                throw new RuntimeException('Falla inducida');
            }
        });
        try {
            $this->confirm(['monto' => '20.00'], [$a->getKey() => '10.00', $b->getKey() => '10.00']);
            $this->fail('La falla inducida debió propagarse.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falla inducida', $exception->getMessage());
        }
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('pago_aplicaciones', 0);
        $this->assertDatabaseCount('consecutivos_pago', 0);
        $this->assertSame('10.00', $a->fresh()->saldo_pendiente);
        $this->assertSame('10.00', $b->fresh()->saldo_pendiente);
    }

    private function cargo(string $saldo, array $overrides = []): Cargo
    {
        return Cargo::create(array_merge([
            'inscripciones_id' => $this->inscripcion->getKey(),
            'concepto_cobro_id' => 1, 'fecha_emision' => '2026-09-01',
            'fecha_vencimiento' => '2026-09-30', 'moneda' => 'MXN',
            'subtotal' => $saldo, 'total' => $saldo, 'saldo_pendiente' => $saldo,
            'estado' => Cargo::ESTADO_PENDIENTE, 'origen' => Cargo::ORIGEN_MANUAL,
        ], $overrides));
    }

    private function confirm(array $data, array $applications): Pago
    {
        return app(AplicarPagoService::class)->confirmar(
            $this->inscripcion->getKey(), $this->metodo->getKey(),
            array_merge(['fecha_pago' => '2026-09-10 10:00:00', 'zona_horaria' => 'UTC'], $data),
            $applications, $this->usuario->getKey()
        );
    }

    private function assertRejected(callable $callback): void
    {
        try {
            $callback();
            $this->fail('La operación debió rechazarse.');
        } catch (ValidationException $exception) {
            $this->assertDatabaseCount('pagos', 0);
        }
    }

    private function assertInvalidChargeIdentifiers(Cargo $cargo, array $applications): void
    {
        $saldo = $cargo->saldo_pendiente;
        $estado = $cargo->estado;
        try {
            $this->confirm(['monto' => '1.00'], $applications);
            $this->fail('El identificador manipulado debió rechazarse.');
        } catch (ValidationException $exception) {
            $this->assertDatabaseCount('pagos', 0);
            $this->assertDatabaseCount('pago_aplicaciones', 0);
            $this->assertDatabaseCount('consecutivos_pago', 0);
            $this->assertSame($saldo, $cargo->fresh()->saldo_pendiente);
            $this->assertSame($estado, $cargo->fresh()->estado);
        }
    }
}
