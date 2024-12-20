<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Espacio extends Model
{
    // use HasFactory;
    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'espacios';

   protected $fillable = ['espacios_nombre','espacios_descripcion','espacios_enlace','espacios_activo','modalidad_id'];

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'espacios_id';
}
