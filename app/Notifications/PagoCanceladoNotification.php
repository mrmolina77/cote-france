<?php

namespace App\Notifications;

use App\Models\Pago;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PagoCanceladoNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public bool $afterCommit = true;
    public function __construct(public int $pagoId) {}
    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        $pago = Pago::query()->with(['comprobantePago', 'cancelledBy'])->findOrFail($this->pagoId);
        abort_unless($pago->estado === Pago::ESTADO_CANCELADO, 409);
        return (new MailMessage())->subject('Pago cancelado - '.$pago->folio)
            ->greeting('Aviso de pago cancelado')
            ->line('El pago '.$pago->folio.' fue cancelado y ya no se considera vigente.')
            ->line('Folio del recibo: '.($pago->comprobantePago?->folio ?: 'Sin recibo'))
            ->line('Fecha de cancelación: '.optional($pago->fecha_cancelacion)->format('Y-m-d H:i'))
            ->line('Motivo: '.(string) $pago->motivo_cancelacion)
            ->line('Canceló: '.($pago->cancelledBy?->name ?: 'Usuario no disponible'))
            ->line('Comprobante interno de pago. Este documento no constituye un CFDI.');
    }
}
