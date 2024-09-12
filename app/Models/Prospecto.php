<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prospecto extends Model
{
    // use HasFactory;

    protected $fillable = ['prospectos_nombres','prospectos_apellidos','prospectos_telefono',
                           'prospectos_correo','origenes_id','seguimientos_id','estatus_id',
                           'prospectos_comentarios','prospectos_fecha','prospectos_clase_fecha',
                           'prospectos_clase_hora'];
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

    public function asistencia(): HasOne
    {
        return $this->hasOne(Asistencia::class, 'prospectos_id');
    }
}
