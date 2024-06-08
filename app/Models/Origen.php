<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Origen extends Model
{
    // use HasFactory;
    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'origenes';

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'origenes_id';

   public function prospectos(){
    return $this->hasMany(Prospecto::class,'origenes_id','origenes_id');
   }
}
