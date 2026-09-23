<?php

namespace App\Jobs;

use App\Models\NotificacionPago;
use App\Models\Pago;
use App\Models\User;
use App\Notifications\PagoCanceladoNotification;
use App\Notifications\PagoRecibidoNotification;
use App\Services\Facturacion\NotificacionPagoService;
use App\Services\Facturacion\AuditoriaPagoService;
use App\Models\AuditoriaPago;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Throwable;

class EnviarNotificacionPago implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [30, 120, 300];
    public function __construct(public int $notificacionPagoId)
    {
        $this->afterCommit = true;
		
    }

    public function handle(NotificacionPagoService $servicio, ?AuditoriaPagoService $auditoria = null): void
    {
        $auditoria ??= app(AuditoriaPagoService::class);
        $limite = now()->subMinutes(10);
        $reclamada = NotificacionPago::query()->whereKey($this->notificacionPagoId)
            ->where(function ($query) use ($limite) {
                $query->whereIn('estado', [NotificacionPago::ESTADO_PENDIENTE, NotificacionPago::ESTADO_FALLIDO])
                    ->orWhere(function ($query) use ($limite) {
                        $query->where('estado', NotificacionPago::ESTADO_PROCESANDO)->where('iniciado_en', '<', $limite);
                    });
            })->update([
                'estado' => NotificacionPago::ESTADO_PROCESANDO, 'iniciado_en' => now(),
                'ultimo_intento_en' => now(), 'intentos' => DB::raw('intentos + 1'), 'ultimo_error' => null,
            ]);
        if ($reclamada !== 1) return;

        $entrega = NotificacionPago::query()->with(['pago.comprobantePago'])->findOrFail($this->notificacionPagoId);
        try {
            if ($entrega->tipo_solicitud === NotificacionPago::SOLICITUD_REENVIO) {
                $usuario = User::query()->find($entrega->solicitado_por);
                if (! $usuario || ! Gate::forUser($usuario)->allows('manage-pagos')) {
                    $this->omitir($entrega, 'La autorización para el reenvío ya no es válida.', $auditoria);
                    return;
                }
            }
            if ($entrega->tipo === NotificacionPago::TIPO_RECIBIDO) {
                $comprobante = $entrega->comprobantePago;
                if ($entrega->pago->estado !== Pago::ESTADO_CONFIRMADO || ! $comprobante
                    || (int) $comprobante->pago_id !== (int) $entrega->pago_id || ! $servicio->comprobanteLegible($comprobante)) {
                    $this->omitir($entrega, 'El pago o el archivo privado del recibo ya no es válido.', $auditoria);
                    return;
                }
                $notification = new PagoRecibidoNotification($entrega->pago_id, $comprobante->getKey());
            } else {
                if ($entrega->pago->estado !== Pago::ESTADO_CANCELADO) {
                    $this->omitir($entrega, 'El pago ya no está cancelado.', $auditoria);
                    return;
                }
                $notification = new PagoCanceladoNotification($entrega->pago_id);
            }
            Notification::route('mail', $entrega->destinatario)->notifyNow($notification);
            $entrega->forceFill(['estado' => NotificacionPago::ESTADO_ENVIADO, 'enviado_en' => now(), 'ultimo_error' => null])->save();
            $auditoria->registrar($entrega->pago, AuditoriaPago::CORREO_ENVIADO, null, ['estado'=>NotificacionPago::ESTADO_PROCESANDO],
                ['estado'=>NotificacionPago::ESTADO_ENVIADO], $this->meta($entrega), null, null);
        } catch (Throwable $e) {
            $entrega->forceFill(['estado' => NotificacionPago::ESTADO_FALLIDO, 'ultimo_error' => $this->sanitizar($e)])->save();
            $auditoria->registrar($entrega->pago, AuditoriaPago::CORREO_FALLIDO, null, ['estado'=>NotificacionPago::ESTADO_PROCESANDO],
                ['estado'=>NotificacionPago::ESTADO_FALLIDO], $this->meta($entrega), null, null);
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        // El callback puede ejecutarse despues de que handle() ya haya registrado
        // el fallo. Solo el intento que quedo efectivamente reclamado conserva el
        // derecho a cerrar la transicion; nunca debe degradar un estado terminal.
        DB::transaction(function () use ($e) {
            $cambio = NotificacionPago::query()->whereKey($this->notificacionPagoId)
                ->where('estado', NotificacionPago::ESTADO_PROCESANDO)->update([
                    'estado' => NotificacionPago::ESTADO_FALLIDO,
                    'ultimo_error' => $this->sanitizar($e), 'ultimo_intento_en' => now(),
                ]);
            if ($cambio === 1 && ($entrega = NotificacionPago::query()->with('pago')->find($this->notificacionPagoId))) {
                app(AuditoriaPagoService::class)->registrar($entrega->pago, AuditoriaPago::CORREO_FALLIDO, null,
                    ['estado'=>NotificacionPago::ESTADO_PROCESANDO],
                    ['estado'=>NotificacionPago::ESTADO_FALLIDO], $this->meta($entrega), null, null);
            }
        });
    }

    private function omitir(NotificacionPago $entrega, string $motivo, AuditoriaPagoService $auditoria): void
    {
        $entrega->forceFill(['estado' => NotificacionPago::ESTADO_OMITIDO, 'ultimo_error' => $motivo])->save();
        $auditoria->registrar($entrega->pago, AuditoriaPago::CORREO_OMITIDO, null,
            ['estado'=>NotificacionPago::ESTADO_PROCESANDO], ['estado'=>NotificacionPago::ESTADO_OMITIDO], $this->meta($entrega), null, null);
    }

    private function meta(NotificacionPago $entrega): array
    {
        return ['notificacion_pago_id'=>(int) $entrega->getKey(), 'intento'=>(int) $entrega->intentos,
            'transicion'=>(string) $entrega->estado];
    }

    private function sanitizar(Throwable $e): string
    {
        return mb_substr('Error de entrega ('.class_basename($e).').', 0, 500);
    }
}
