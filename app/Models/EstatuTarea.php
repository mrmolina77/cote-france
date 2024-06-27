<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstatuTarea extends Model
{
    // use HasFactory;
    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'estatu_tareas';

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'est_tareas_id';

   public function tareas(){
    return $this->hasMany(Tarea::class,'est_tareas_id','est_tareas_id');
   }
}
