<?php

namespace App\Models;

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

   protected $fillable = ['horarios_id','diarios_descripcion'];

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


}
