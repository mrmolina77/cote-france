<?php

namespace Tests\Feature;

use App\Jobs\EnviarNotificacionPago;
use App\Models\NotificacionPago;
use App\Services\Facturacion\CancelarPagoService;
use Illuminate\Support\Facades\Queue;

class PagoCanceladoNotificationTest extends ComprobantePagoTestCase
{
    public function test_cancellation_creates_one_queued_event_without_changing_financial_result(): void
    {
        Queue::fake(); $admin = $this->user('admin'); ['pago' => $pago, 'cargos' => $cargos] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $cancelado = app(CancelarPagoService::class)->cancelar($pago->getKey(), 'Pago duplicado', $admin->getKey());
        $this->assertSame('cancelado', $cancelado->estado);
        $this->assertSame(['10.00', '5.00'], array_map(fn ($cargo) => $cargo->fresh()->saldo_pendiente, $cargos));
        $this->assertDatabaseCount('notificaciones_pago', 1);
        $this->assertDatabaseHas('notificaciones_pago', ['tipo' => NotificacionPago::TIPO_CANCELADO, 'tipo_solicitud' => 'inicial']);
        Queue::assertPushed(EnviarNotificacionPago::class, 1);
    }
}
