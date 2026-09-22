<?php

namespace Tests\Feature;

use App\Jobs\EnviarNotificacionPago;
use App\Models\NotificacionPago;
use App\Notifications\PagoRecibidoNotification;
use App\Services\Facturacion\GeneradorComprobantePagoService;
use App\Services\Facturacion\NotificacionPagoService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class PagoRecibidoNotificationTest extends ComprobantePagoTestCase
{
    public function test_queued_delivery_attaches_exact_private_pdf_and_updates_audit(): void
    {
        Storage::fake('local'); Queue::fake(); Notification::fake();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $entrega = app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo);
        Queue::assertPushed(EnviarNotificacionPago::class, fn ($job) => $job->notificacionPagoId === $entrega->getKey());

        (new EnviarNotificacionPago($entrega->getKey()))->handle(app(NotificacionPagoService::class));
        Notification::assertSentOnDemand(PagoRecibidoNotification::class, function ($notification) use ($recibo) {
            $mail = $notification->toMail(null);
            $this->assertSame('Pago recibido - '.$recibo->folio, $mail->subject);
            $this->assertStringContainsString('Comprobante interno de pago. Este documento no constituye un CFDI.', implode(' ', $mail->introLines));
            $this->assertSame(Storage::disk('local')->get($recibo->ruta_pdf), $mail->rawAttachments[0]['data']);
            $this->assertSame('application/pdf', $mail->rawAttachments[0]['options']['mime']);
            return true;
        });
        $this->assertSame(NotificacionPago::ESTADO_ENVIADO, $entrega->fresh()->estado);
        $this->assertSame(1, $entrega->fresh()->intentos);
    }

    public function test_invalid_recipient_is_audited_without_queueing(): void
    {
        Storage::fake('local'); Queue::fake();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $entrega = app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo);
        $this->assertSame(NotificacionPago::ESTADO_OMITIDO, $entrega->estado);
        Queue::assertNothingPushed();
        $this->assertSame('confirmado', $pago->fresh()->estado);
    }
}
