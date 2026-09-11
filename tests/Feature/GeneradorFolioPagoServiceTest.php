<?php

namespace Tests\Feature;

use App\Models\Pago;
use App\Services\Facturacion\GeneradorFolioPagoService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GeneradorFolioPagoServiceTest extends PagosTestCase
{
    private GeneradorFolioPagoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeneradorFolioPagoService();
    }

    public function test_first_increment_restart_and_exact_format(): void
    {
        $this->assertSame('PAG-2026-000001', $this->service->generar('2026-01-01 00:00:00', 'America/Mexico_City'));
        $this->assertSame('PAG-2026-000002', $this->service->generar('2026-12-31 23:59:59', 'America/Mexico_City'));
        $this->assertSame('PAG-2027-000001', $this->service->generar('2027-01-01 00:00:00', 'America/Mexico_City'));
        $this->assertDatabaseHas('consecutivos_pago', ['anio' => 2026, 'ultimo_consecutivo' => 2]);
        $this->assertDatabaseHas('consecutivos_pago', ['anio' => 2027, 'ultimo_consecutivo' => 1]);
    }

    public function test_year_comes_from_payment_instant_in_its_timezone(): void
    {
        $instant = CarbonImmutable::parse('2027-01-01 01:30:00', 'UTC');
        $this->assertSame('PAG-2026-000001', $this->service->generar($instant, 'America/Mexico_City'));
        $this->assertSame('PAG-2027-000001', $this->service->generar($instant, 'Europe/Paris'));
    }

    public function test_counter_exceeding_six_digits_is_not_truncated(): void
    {
        DB::table('consecutivos_pago')->insert(['anio' => 2026, 'ultimo_consecutivo' => 999999]);
        $this->assertSame('PAG-2026-1000000', $this->service->generar('2026-06-01', 'UTC'));
    }

    public function test_payments_count_state_or_deletion_never_reuses_a_reserved_folio(): void
    {
        $first = $this->service->generar('2026-06-01', 'UTC');
        $pago = Pago::create($this->paymentAttributes(['folio' => $first]));
        $pago->forceFill(['estado' => Pago::ESTADO_CANCELADO])->save();
        $this->assertSame(Pago::ESTADO_CANCELADO, $pago->fresh()->estado);
        $this->assertSame('PAG-2026-000002', $this->service->generar('2026-06-01', 'UTC'));
        DB::table('pagos')->where('pago_id', $pago->getKey())->delete();
        $this->assertSame('PAG-2026-000003', $this->service->generar('2026-06-01', 'UTC'));
    }

    public function test_outer_transaction_rollback_also_rolls_back_counter_reservation(): void
    {
        try {
            DB::transaction(function () {
                $this->assertSame('PAG-2028-000001', $this->service->generar('2028-01-01', 'UTC'));
                throw new RuntimeException('Falla simulada al insertar el pago.');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Falla simulada al insertar el pago.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('consecutivos_pago', ['anio' => 2028]);
        $this->assertSame('PAG-2028-000001', $this->service->generar('2028-01-01', 'UTC'));
    }
}
