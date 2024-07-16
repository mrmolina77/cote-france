<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasePrueba extends Model
{
    // use HasFactory;
    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'clases_pruebas';

   protected $fillable = ['clasespruebas_fecha','clasespruebas_hora_inicio',
                          'clasespruebas_hora_fin','profesores_id'];

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'clasespruebas_id';

   public function profesor()
    {
        return $this->belongsTo(Profesor::class,'profesores_id','profesores_id');
    }
}
