<?php

namespace Tests\Feature;

use App\Jobs\EnviarNotificacionPago;
use App\Models\NotificacionPago;
use App\Models\AuditoriaPago;
use App\Services\Facturacion\CancelarPagoService;
use App\Services\Facturacion\GeneradorComprobantePagoService;
use App\Services\Facturacion\NotificacionPagoService;
use App\Notifications\PagoCanceladoNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PagoCanceladoNotificationTest extends ComprobantePagoTestCase
{
    public function test_cancellation_creates_one_queued_event_without_changing_financial_result(): void
    {
        Storage::fake('local'); Queue::fake(); Notification::fake();
        $admin = $this->user('admin'); ['pago' => $pago, 'cargos' => $cargos] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $cancelado = app(CancelarPagoService::class)->cancelar($pago->getKey(), 'Pago duplicado', $admin->getKey());
        $this->assertSame('cancelado', $cancelado->estado);
        $this->assertSame(['10.00', '5.00'], array_map(fn ($cargo) => $cargo->fresh()->saldo_pendiente, $cargos));
        $this->assertDatabaseCount('notificaciones_pago', 1);
        $this->assertDatabaseHas('notificaciones_pago', ['tipo' => NotificacionPago::TIPO_CANCELADO, 'tipo_solicitud' => 'inicial']);
        Queue::assertPushed(EnviarNotificacionPago::class, 1);

        $entrega = NotificacionPago::query()->firstOrFail();
        (new EnviarNotificacionPago($entrega->getKey()))->handle(app(NotificacionPagoService::class));
        Notification::assertSentOnDemand(PagoCanceladoNotification::class, function ($notification) use ($cancelado, $recibo, $admin) {
            $mail = $notification->toMail(null);
            $texto = implode(' ', $mail->introLines);
            $this->assertSame('Pago cancelado - '.$cancelado->folio, $mail->subject);
            $this->assertSame('Aviso de pago cancelado', $mail->greeting);
            $this->assertStringContainsString($cancelado->folio, $texto);
            $this->assertStringContainsString($recibo->folio, $texto);
            $this->assertStringContainsString($cancelado->fecha_cancelacion->format('Y-m-d H:i'), $texto);
            $this->assertStringContainsString('Pago duplicado', $texto);
            $this->assertStringContainsString($admin->name, $texto);
            $this->assertStringContainsString('ya no se considera vigente', $texto);
            $this->assertStringContainsString('Comprobante interno de pago. Este documento no constituye un CFDI.', $texto);
            $this->assertEmpty($mail->attachments);
            $this->assertEmpty($mail->rawAttachments);
            return true;
        });
        $this->assertSame(NotificacionPago::ESTADO_ENVIADO, $entrega->fresh()->estado);
        $this->assertSame(1, $entrega->fresh()->intentos);
        $this->assertNotNull($entrega->fresh()->enviado_en);
        $evento = AuditoriaPago::where('pago_id', $pago->getKey())
            ->where('accion', AuditoriaPago::CORREO_ENVIADO)->sole();
        $this->assertSame(['estado' => NotificacionPago::ESTADO_PROCESANDO], $evento->valores_anteriores);
        $this->assertSame(['estado' => NotificacionPago::ESTADO_ENVIADO], $evento->valores_nuevos);
        $this->assertSame($entrega->getKey(), $evento->metadatos['notificacion_pago_id']);
        $this->assertSame(1, $evento->metadatos['intento']);
        $this->assertSame(NotificacionPago::ESTADO_ENVIADO, $evento->metadatos['transicion']);
    }

    public function test_cancellation_job_obeys_outer_commit_and_rollback_restores_finances(): void
    {
        Storage::fake('local'); Notification::fake();
        (require database_path('migrations/2026_09_22_000002_create_jobs_table.php'))->up();
        config()->set('queue.default', 'database');
        $admin = $this->user('admin'); ['pago' => $pago, 'cargos' => $cargos] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        DB::beginTransaction();
        app(CancelarPagoService::class)->cancelar($pago->getKey(), 'Prueba rollback', $admin->getKey());
        $this->assertSame(0, DB::table('jobs')->count());
        DB::rollBack();
        $this->assertSame('confirmado', $pago->fresh()->estado);
        $this->assertSame(['0.00', '0.00'], array_map(fn ($cargo) => $cargo->fresh()->saldo_pendiente, $cargos));
        $this->assertDatabaseCount('notificaciones_pago', 0);
        $this->assertSame(0, DB::table('jobs')->count());
        Notification::assertNothingSent();

        DB::beginTransaction();
        app(CancelarPagoService::class)->cancelar($pago->getKey(), 'Prueba commit', $admin->getKey());
        $this->assertSame(0, DB::table('jobs')->count());
        DB::commit();
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame('cancelado', $pago->fresh()->estado);
    }
}
