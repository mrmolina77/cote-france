<?php

namespace App\Models;

use App\Models\Tematica;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diario extends Model
{
    // use HasFactory;
    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'diarios';

   protected $fillable = ['horarios_id','diarios_hecho','diarios_porhacer','niveles_id','capitulos_id','tematica_id','validado_datos_generales','validado_contenido_clase','validado_estudiantes','validado_prospectos'];

   protected $casts = [
        'validado_datos_generales' => 'boolean',
        'validado_contenido_clase' => 'boolean',
        'validado_estudiantes' => 'boolean',
        'validado_prospectos' => 'boolean',
   ];

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'diarios_id';

   public function diario()
    {
        return $this->hasOne(Diario::class, 'horarios_id', 'horarios_id');
    }


   public function tematica()
    {
        return $this->belongsTo(Tematica::class, 'tematica_id', 'tematica_id');
    }

}
