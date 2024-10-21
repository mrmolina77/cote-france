<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hora extends Model
{
    // use HasFactory;
    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'horas';

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'horas_id';
}
