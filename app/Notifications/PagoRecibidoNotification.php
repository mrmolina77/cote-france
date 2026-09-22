<?php

namespace App\Notifications;

use App\Models\ComprobantePago;
use App\Models\Pago;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class PagoRecibidoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $pagoId, public int $comprobanteId)
    {
        $this->afterCommit = true;
    }
    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        $pago = Pago::query()->with(['prospecto', 'metodoPago'])->findOrFail($this->pagoId);
        $comprobante = ComprobantePago::query()->whereKey($this->comprobanteId)->where('pago_id', $pago->getKey())->firstOrFail();
        abort_unless($pago->estado === Pago::ESTADO_CONFIRMADO, 409);
        abort_unless($comprobante->disco === ComprobantePago::DISCO_PRIVADO
            && $comprobante->mime_type === ComprobantePago::MIME_PDF
            && str_starts_with($comprobante->ruta_pdf, ComprobantePago::DIRECTORIO.'/')
            && ! str_contains($comprobante->ruta_pdf, '..'), 409);
        $disco = Storage::disk(ComprobantePago::DISCO_PRIVADO);
        abort_unless($disco->exists($comprobante->ruta_pdf), 409);
        $bytes = $disco->get($comprobante->ruta_pdf);
        abort_unless(hash_equals($comprobante->hash_sha256, hash('sha256', $bytes)), 409);

        return (new MailMessage())
            ->subject('Pago recibido - '.$comprobante->folio)
            ->greeting('Pago recibido')
            ->line('Folio: '.$comprobante->folio)
            ->line('Fecha: '.optional($pago->fecha_pago)->format('Y-m-d H:i'))
            ->line('Alumno: '.trim((string) $pago->prospecto?->prospectos_nombres.' '.(string) $pago->prospecto?->prospectos_apellidos))
            ->line('Importe: '.$pago->moneda.' $'.$pago->monto)
            ->line('Método: '.($pago->metodoPago?->nombre ?: 'No especificado'))
            ->line('Comprobante interno de pago. Este documento no constituye un CFDI.')
            ->attachData($bytes, $comprobante->folio.'.pdf', ['mime' => ComprobantePago::MIME_PDF]);
    }
}
