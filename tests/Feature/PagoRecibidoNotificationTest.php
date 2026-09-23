<?php

namespace Tests\Feature;

use App\Jobs\EnviarNotificacionPago;
use App\Models\AuditoriaPago;
use App\Models\NotificacionPago;
use App\Models\Pago;
use App\Notifications\PagoRecibidoNotification;
use App\Services\Facturacion\GeneradorComprobantePagoService;
use App\Services\Facturacion\NotificacionPagoService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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
        $this->assertNotNull($entrega->fresh()->enviado_en);
    }

    /** @dataProvider metadatosInvalidos */
    public function test_invalid_private_pdf_is_omitted_before_queueing(string $campo, string $valor): void
    {
        Storage::fake('local'); Queue::fake();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $recibo->forceFill([$campo => $valor])->save();

        $entrega = app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo);

        $this->assertSame(NotificacionPago::ESTADO_OMITIDO, $entrega->estado);
        $this->assertSame('El pago o el archivo privado del recibo no es válido.', $entrega->ultimo_error);
        Queue::assertNothingPushed();
        $this->assertSame('confirmado', $pago->fresh()->estado);
    }

    public function metadatosInvalidos(): array
    {
        return [['hash_sha256', str_repeat('f', 64)], ['disco', 'public'], ['mime_type', 'text/plain'], ['ruta_pdf', '../recibo.pdf']];
    }

    public function test_missing_pdf_and_confirmed_payment_without_receipt_are_omitted(): void
    {
        Storage::fake('local'); Queue::fake();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        Storage::disk('local')->delete($recibo->ruta_pdf);
        $this->assertSame(NotificacionPago::ESTADO_OMITIDO,
            app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo)->estado);
        Queue::assertNothingPushed();

        ['pago' => $otro] = $this->pagoConfirmado($admin);
        $otro->responsablePago->update(['correo' => 'other@example.com']);
        $this->assertSame(NotificacionPago::ESTADO_OMITIDO,
            app(NotificacionPagoService::class)->solicitarInicialRecibido($otro, null)->estado);
    }

    public function test_non_confirmed_payment_is_omitted_without_changing_financial_state(): void
    {
        foreach (['borrador', 'cancelado', 'reembolsado'] as $estado) {
            Storage::fake('local'); Queue::fake();
            $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
            $pago->responsablePago->update(['correo' => 'payer@example.com']);
            $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
            $pago->forceFill(['estado' => $estado])->save();
            $this->assertSame(NotificacionPago::ESTADO_OMITIDO,
                app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo)->estado);
            $this->assertSame($estado, $pago->fresh()->estado);
            Queue::assertNothingPushed();
        }
    }

    public function test_job_omits_receipt_corrupted_after_scheduling(): void
    {
        Storage::fake('local'); Queue::fake(); Notification::fake();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $entrega = app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo);
        Storage::disk('local')->put($recibo->ruta_pdf, 'corrupto');
        (new EnviarNotificacionPago($entrega->getKey()))->handle(app(NotificacionPagoService::class));
        $this->assertSame(NotificacionPago::ESTADO_OMITIDO, $entrega->fresh()->estado);
        $this->assertSame(1, $entrega->fresh()->intentos);
        Notification::assertNothingSent();
    }

    public function test_database_job_becomes_available_only_after_outer_commit(): void
    {
        Storage::fake('local'); Notification::fake();
        (require database_path('migrations/2026_09_22_000002_create_jobs_table.php'))->up();
        config()->set('queue.default', 'database');
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        DB::beginTransaction();
        app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo);
        $this->assertSame(0, DB::table('jobs')->count());
        DB::commit();
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame(Pago::ESTADO_CONFIRMADO, $pago->fresh()->estado);
    }

    public function test_outer_rollback_removes_delivery_and_discards_after_commit_job(): void
    {
        Storage::fake('local'); Notification::fake();
        (require database_path('migrations/2026_09_22_000002_create_jobs_table.php'))->up();
        config()->set('queue.default', 'database');
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        DB::beginTransaction();
        app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo);
        DB::rollBack();
        $this->assertDatabaseCount('notificaciones_pago', 0);
        $this->assertSame(0, DB::table('jobs')->count());
        Notification::assertNothingSent();
    }

    public function test_transport_failure_is_audited_and_retry_keeps_attempt_counter(): void
    {
        Storage::fake('local'); Queue::fake();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $entrega = app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo);
        Notification::shouldReceive('sendNow')->once()->andThrow(new \RuntimeException('smtp://secret-token@private-host/path'));
        try {
            (new EnviarNotificacionPago($entrega->getKey()))->handle(app(NotificacionPagoService::class));
            $this->fail('El transporte debía fallar.');
        } catch (\RuntimeException $e) {
            $this->assertSame(NotificacionPago::ESTADO_FALLIDO, $entrega->fresh()->estado);
            $this->assertSame(1, $entrega->fresh()->intentos);
            $this->assertSame('Error de entrega (RuntimeException).', $entrega->fresh()->ultimo_error);
        }
        Notification::shouldReceive('sendNow')->once()->andReturnNull();
        (new EnviarNotificacionPago($entrega->getKey()))->handle(app(NotificacionPagoService::class));
        $this->assertSame(NotificacionPago::ESTADO_ENVIADO, $entrega->fresh()->estado);
        $this->assertSame(2, $entrega->fresh()->intentos);
        $this->assertSame(Pago::ESTADO_CONFIRMADO, $pago->fresh()->estado);
    }

    public function test_failed_callback_sanitizes_permanent_error_without_increment_reset(): void
    {
        Storage::fake('local'); Queue::fake();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $entrega = app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo);
        $entrega->forceFill(['intentos' => 3, 'estado' => NotificacionPago::ESTADO_PROCESANDO])->save();
        (new EnviarNotificacionPago($entrega->getKey()))->failed(new \RuntimeException('password=secret /private/file.pdf'));
        $this->assertSame(NotificacionPago::ESTADO_FALLIDO, $entrega->fresh()->estado);
        $this->assertSame(3, $entrega->fresh()->intentos);
        $this->assertSame('Error de entrega (RuntimeException).', $entrega->fresh()->ultimo_error);
        $evento = AuditoriaPago::where('pago_id', $pago->getKey())->where('accion', AuditoriaPago::CORREO_FALLIDO)->sole();
        $this->assertSame(['estado' => NotificacionPago::ESTADO_PROCESANDO], $evento->valores_anteriores);
        $this->assertSame(['estado' => NotificacionPago::ESTADO_FALLIDO], $evento->valores_nuevos);
    }

    /** @dataProvider estadosTerminalesYNoReclamados */
    public function test_failed_callback_does_not_change_or_audit_unclaimed_or_terminal_delivery(string $estado): void
    {
        Storage::fake('local'); Queue::fake();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $entrega = app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo);
        $entrega->forceFill(['estado' => $estado, 'ultimo_error' => 'sin cambios'])->save();
        $auditoriasAntes = AuditoriaPago::where('pago_id', $pago->getKey())->count();

        (new EnviarNotificacionPago($entrega->getKey()))->failed(new \RuntimeException('token=secreto'));

        $this->assertSame($estado, $entrega->fresh()->estado);
        $this->assertSame('sin cambios', $entrega->fresh()->ultimo_error);
        $this->assertSame($auditoriasAntes, AuditoriaPago::where('pago_id', $pago->getKey())->count());
    }

    public function estadosTerminalesYNoReclamados(): array
    {
        return array_map(fn ($estado) => [$estado], [
            NotificacionPago::ESTADO_PENDIENTE,
            NotificacionPago::ESTADO_FALLIDO,
            NotificacionPago::ESTADO_ENVIADO,
            NotificacionPago::ESTADO_OMITIDO,
        ]);
    }

    public function test_failed_callback_ignores_missing_delivery(): void
    {
        (new EnviarNotificacionPago(PHP_INT_MAX))->failed(new \RuntimeException('token=secreto'));

        $this->assertDatabaseCount('auditoria_pagos', 0);
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
