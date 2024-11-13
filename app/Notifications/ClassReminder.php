<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClassReminder extends Notification
{
    use Queueable;

    protected $prospecto;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($prospecto)
    {
        //
        $this->prospecto = $prospecto;
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
        return (new MailMessage)
                    ->greeting('Hola '.$this->prospecto->prospectos_nombres.' '.$this->prospecto->prospectos_apellidos.':')
                    ->line('Te recordamos que tienes una clase de prueba programada con nosotros el dia '
                           .\Carbon\Carbon::parse($this->prospecto->horario->horarios_dia)->format('d-m-Y')
                           .' a las '.$this->prospecto->horario->hora->horas_desde)
                    ->line('En esta sesión, tendrás la oportunidad de conocer el contenido y la dinámica de nuestras clases, resolver dudas y confirmar si este es el curso adecuado para ti.')
                    ->line('Por favor, asegúrate de estar listo/a unos minutos antes para aprovechar al máximo el tiempo de la sesión.')
                    ->line('Si tienes alguna pregunta o necesitas reprogramar la clase, no dudes en contactarnos.')
                    ->line('¡Nos vemos pronto!')
                    ->line('Saludos cordiales,');
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
