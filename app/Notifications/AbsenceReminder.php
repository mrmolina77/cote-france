<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbsenceReminder extends Notification
{
    use Queueable;

    protected $prospecto;
    protected $grupoNombre;
    protected $totalInasistencias;
    protected $proximaClase;

    public function __construct($prospecto, $grupoNombre, $totalInasistencias, $proximaClase)
    {
        $this->prospecto = $prospecto;
        $this->grupoNombre = $grupoNombre;
        $this->totalInasistencias = $totalInasistencias;
        $this->proximaClase = $proximaClase;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mensaje = (new MailMessage)
            ->greeting('Hola '.$this->prospecto->prospectos_nombres.' '.$this->prospecto->prospectos_apellidos.':')
            ->line('Hemos registrado '.$this->totalInasistencias.' inasistencias en tu grupo '.$this->grupoNombre.'.')
            ->line('Te invitamos cordialmente a asistir a la próxima clase del grupo al que perteneces.');

        if ($this->proximaClase) {
            $fecha = Carbon::parse($this->proximaClase->horarios_dia)->format('d-m-Y');
            $hora = $this->proximaClase->hora?->horas_desde;
            $detalleHora = $hora ? ' a las '.$hora : '';

            $mensaje->line('La próxima clase será el '.$fecha.$detalleHora.'.');
        } else {
            $mensaje->line('Pronto compartiremos la fecha y hora de la siguiente sesión.');
        }

        return $mensaje->line('Si necesitas apoyo o tienes alguna duda, estamos para ayudarte.')
            ->line('Saludos cordiales.');
    }

    public function toArray($notifiable)
    {
        return [];
    }
}
