<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    // use HasFactory;

    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'asistencias';

   protected $fillable = ['prospectos_id','clasespruebas_id',
                          'asistencias','asistencias_fecha'];

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'asistencias_id';

   public function prospecto()
    {
        return $this->belongsTo(Prospecto::class,'prospectos_id','prospectos_id');
    }
   public function claseprueba()
    {
        return $this->belongsTo(ClasePrueba::class,'clasespruebas_id','clasespruebas_id');
    }

    public function asistio()
    {
        if ($this->asistencias == 0) {
            return 'NO';
        } else {
            return 'SI';
        }
    }
}
