<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    // use HasFactory;
    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'cursos';

   protected $fillable = [
       'cursos_descripcion',
       'cursos_fecha_creacion',
       'cursos_activo',
   ];

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'cursos_id';
}
