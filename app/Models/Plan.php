<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    // use HasFactory;

    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'planes';

   protected $fillable = ['horarios_id','planes_descripcion'];

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'planes_id';
}
