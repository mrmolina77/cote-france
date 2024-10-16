<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClassCreated extends Notification
{
    use Queueable;

    private $prospecto;

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
                    ->greeting('¡Hola!')
                    ->line('El prospecto '.$this->prospecto->prospectos_nombres.' '.$this->prospecto->prospectos_apellidos
                         .' tiene una clase programada para el dia '.\Carbon\Carbon::parse($this->prospecto->prospectos_clase_fecha)->format('d-m-Y')
                         .' a las '.$this->prospecto->prospectos_clase_hora.' puede contactar por el '.$this->prospecto->prospectos_telefono.' o por el correo'
                         .$this->prospecto->prospectos_correo)
                    ->line('¡Por favor estar pendiente de hacerle seguimiento!');
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
