<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClassReminder extends Notification
{
    use Queueable;

    protected $clasePrueba;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($clasePrueba)
    {
        //
        $this->clasePrueba = $clasePrueba;
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
        $prospecto = $this->clasePrueba->prospecto;
        $fecha = \Carbon\Carbon::parse($this->clasePrueba->horarios_dia)->format('d-m-Y');
        $hora = $this->clasePrueba->hora ? $this->clasePrueba->hora->horas_desde : 'N/A';

        return (new MailMessage)
                    ->greeting('Hola '.$prospecto->prospectos_nombres.' '.$prospecto->prospectos_apellidos.':')
                    ->line('Te recordamos que tienes una clase de prueba programada con nosotros el dia '
                           .$fecha
                           .' a las '.$hora)
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
