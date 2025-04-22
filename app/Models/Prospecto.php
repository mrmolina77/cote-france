<?php

namespace App\Models;
use Illuminate\Notifications\Notifiable;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Prospecto extends Model
{
    // use HasFactory;
    use Notifiable;

    protected $fillable = ['prospectos_nombres','prospectos_apellidos','prospectos_telefono1',
                           'prospectos_telefono2','profesores_fecha_ingreso','origenes_id',
                           'seguimientos_id','estatus_id','prospectos_comentarios','prospectos_correo',
                           'prospectos_fecha','prospectos_clase_fecha','prospectos_clase_hora',
                           'grupo_id','horarios_id','modalidad_id'];
    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'prospectos';

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'prospectos_id';

   public function origen()
    {
        return $this->belongsTo(Origen::class,'origenes_id','origenes_id');
    }
   public function segumiento()
    {
        return $this->belongsTo(Seguimiento::class,'seguientos_id','seguimientos_id');
    }
   public function Estatu()
    {
        return $this->belongsTo(Estatu::class,'estatus_id','estatus_id');
    }

    public function asistencia()
    {
        return $this->hasMany(Asistencia::class, 'prospectos_id', 'prospectos_id');
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class, 'horarios_id', 'horarios_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'prospectos_id', 'prospectos_id');
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class, 'prospectos_id', 'prospectos_id');
    }

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForMail($notification)
    {
        // Return email address only...
        return $this->prospectos_correo;
    }
}
