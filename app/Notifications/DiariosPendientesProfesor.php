<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DiariosPendientesProfesor extends Notification
{
    use Queueable;

    protected array $clases;

    /**
     * Create a new notification instance.
     *
     * @param  array  $clases
     * @return void
     */
    public function __construct(array $clases)
    {
        $this->clases = $clases;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('Clases pendientes de actualización')
            ->greeting('Hola '.$notifiable->nombre_completo.':')
            ->line('Tienes clases que ya pasaron su día y aún no han sido actualizadas en el diario.')
            ->line('Por favor realiza la actualización lo antes posible.');

        foreach ($this->clases as $clase) {
            $dia = $clase['dia'] ?? 'N/A';
            $hora = $clase['hora'] ?? 'N/A';
            $grupo = $clase['grupo'] ?? 'N/A';

            $mail->line("• {$dia} | {$hora} | Grupo: {$grupo}");
        }

        return $mail->line('Gracias por tu atención.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
