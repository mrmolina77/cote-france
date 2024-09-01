<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    // use HasFactory;

    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'tareas';

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'tareas_id';

   protected $fillable = ['tareas_descripcion','tareas_fecha','tareas_comentario',
                           'prospectos_id','est_tareas_id','user_id'];

    public function prospecto()
    {
        return $this->belongsTo(Prospecto::class,'prospectos_id','prospectos_id');
    }
    public function estatus()
    {
        return $this->belongsTo(EstatuTarea::class,'est_tareas_id','est_tareas_id');
    }
}
