<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    // use HasFactory;
    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'inscripciones';

   protected $fillable = ['fecha_inscripcion','prospectos_id','cursos_id','grupo_id'];

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'inscripciones_id';

    public function prospecto()
    {
        return $this->belongsTo(Prospecto::class,'prospectos_id','prospectos_id');
    }

    public function cursos()
    {
        return $this->belongsTo(Curso::class,'cursos_id','cursos_id');
    }

}
