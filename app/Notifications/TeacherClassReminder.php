<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeacherClassReminder extends Notification
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
        $profesor = $this->clasePrueba->profesor;
        $prospecto = $this->clasePrueba->prospecto;
        $fecha = \Carbon\Carbon::parse($this->clasePrueba->horarios_dia)->format('d-m-Y');
        $hora = $this->clasePrueba->hora ? $this->clasePrueba->hora->horas_desde : 'N/A';
        $modalidad = $this->clasePrueba->modalidad ? $this->clasePrueba->modalidad->modalidad_nombre : 'N/A';
        $espacio = $this->clasePrueba->espacio ? $this->clasePrueba->espacio->espacios_nombre : 'N/A';

        $nombreProfesor = $profesor ? ($profesor->profesores_nombres . ' ' . $profesor->profesores_apellidos) : 'Profesor';
        $nombreProspecto = $prospecto ? ($prospecto->prospectos_nombres . ' ' . $prospecto->prospectos_apellidos) : 'Estudiante';

        return (new MailMessage)
                    ->subject('Recordatorio de Clase de Prueba - ' . $nombreProspecto)
                    ->greeting('Hola ' . $nombreProfesor . ':')
                    ->line('Te recordamos que tienes una clase de prueba programada para mañana con el alumno ' . $nombreProspecto . '.')
                    ->line('Detalles de la clase de prueba:')
                    ->line('• Fecha: ' . $fecha)
                    ->line('• Hora: ' . $hora)
                    ->line('• Modalidad: ' . $modalidad)
                    ->line('• Espacio/Aula: ' . $espacio)
                    ->line('Por favor, asegúrate de estar disponible unos minutos antes para recibir al alumno.')
                    ->line('¡Muchas gracias por tu compromiso!');
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
