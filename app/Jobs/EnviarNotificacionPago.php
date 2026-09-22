<?php

namespace App\Jobs;

use App\Models\NotificacionPago;
use App\Models\Pago;
use App\Models\User;
use App\Notifications\PagoCanceladoNotification;
use App\Notifications\PagoRecibidoNotification;
use App\Services\Facturacion\NotificacionPagoService;
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

    public function handle(NotificacionPagoService $servicio): void
    {
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
                    $this->omitir($entrega, 'La autorización para el reenvío ya no es válida.');
                    return;
                }
            }
            if ($entrega->tipo === NotificacionPago::TIPO_RECIBIDO) {
                $comprobante = $entrega->comprobantePago;
                if ($entrega->pago->estado !== Pago::ESTADO_CONFIRMADO || ! $comprobante
                    || (int) $comprobante->pago_id !== (int) $entrega->pago_id || ! $servicio->comprobanteLegible($comprobante)) {
                    $this->omitir($entrega, 'El pago o el archivo privado del recibo ya no es válido.');
                    return;
                }
                $notification = new PagoRecibidoNotification($entrega->pago_id, $comprobante->getKey());
            } else {
                if ($entrega->pago->estado !== Pago::ESTADO_CANCELADO) {
                    $this->omitir($entrega, 'El pago ya no está cancelado.');
                    return;
                }
                $notification = new PagoCanceladoNotification($entrega->pago_id);
            }
            Notification::route('mail', $entrega->destinatario)->notifyNow($notification);
            $entrega->forceFill(['estado' => NotificacionPago::ESTADO_ENVIADO, 'enviado_en' => now(), 'ultimo_error' => null])->save();
        } catch (Throwable $e) {
            $entrega->forceFill(['estado' => NotificacionPago::ESTADO_FALLIDO, 'ultimo_error' => $this->sanitizar($e)])->save();
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        NotificacionPago::query()->whereKey($this->notificacionPagoId)->update([
            'estado' => NotificacionPago::ESTADO_FALLIDO,
            'ultimo_error' => $this->sanitizar($e), 'ultimo_intento_en' => now(),
        ]);
    }

    private function omitir(NotificacionPago $entrega, string $motivo): void
    {
        $entrega->forceFill(['estado' => NotificacionPago::ESTADO_OMITIDO, 'ultimo_error' => $motivo])->save();
    }

    private function sanitizar(Throwable $e): string
    {
        return mb_substr('Error de entrega ('.class_basename($e).').', 0, 500);
    }
}
