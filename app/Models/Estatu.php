<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estatu extends Model
{
    // use HasFactory;
    
    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'estatus';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'estatus_id';
}
