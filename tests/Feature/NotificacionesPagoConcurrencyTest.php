<?php

namespace Tests\Feature;

use App\Services\Facturacion\GeneradorComprobantePagoService;
use App\Services\Facturacion\NotificacionPagoService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class NotificacionesPagoConcurrencyTest extends ComprobantePagoTestCase
{
    public function test_unique_constraint_makes_repeated_initial_and_same_resend_action_idempotent(): void
    {
        Storage::fake('local'); Queue::fake();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $receipt = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $service = app(NotificacionPagoService::class);
        $service->solicitarInicialRecibido($pago, $receipt); $service->solicitarInicialRecibido($pago, $receipt);
        $service->solicitarReenvio($pago, $receipt, $admin->getKey(), 'same-action');
        $service->solicitarReenvio($pago, $receipt, $admin->getKey(), 'same-action');
        $this->assertDatabaseCount('notificaciones_pago', 2);
        Queue::assertPushed(\App\Jobs\EnviarNotificacionPago::class, 2);
    }
}
