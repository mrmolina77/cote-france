<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    // use HasFactory;

    protected $table = 'grupos';

    protected $fillable = ['grupo_nombre','grupo_nivel','grupo_capitulo','grupo_libro_maestro','grupo_libro_alumno','grupo_observacion','modalidad_id','estado_id','profesores_id'];


    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'grupo_id';


    public function estado()
    {
        return $this->belongsTo(Estado::class,'estado_id','estado_id');
    }

    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class,'modalidad_id','modalidad_id');
    }
    
}
