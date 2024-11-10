<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClassCreated extends Notification
{
    use Queueable;

    private $datos;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($datos)
    {
        //
        $this->datos = $datos;
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
                    ->subject('Recordatorio de Clase de Prueba')
                    ->greeting('¡Hola!')
                    ->line('El prospecto '.$this->datos['nombre'].' tiene una clase programada para el dia '
                         .\Carbon\Carbon::parse($this->datos['fecha'])->format('d-m-Y')
                         .' a las '.$this->datos['hora'].' puede contactar por el '.$this->datos['prospectos_telefono']
                         .' o por el correo '.$this->datos['prospectos_correo'])
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
