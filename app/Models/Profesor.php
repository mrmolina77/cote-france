<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profesor extends Model
{
    // use HasFactory;

    protected $fillable = ['profesores_nombres','profesores_apellidos','profesores_email',
                           'profesores_color','profesores_horas_semanales','profesores_fecha_ingreso'];

    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'profesores';

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'profesores_id';


    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getNombreCompletoAttribute()
    {
        return "{$this->profesores_nombres} {$this->profesores_apellidos}";
    }
}
