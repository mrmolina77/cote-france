<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modalidad extends Model
{
    // use HasFactory;

    protected $table = 'modalidades';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'modalidad_id';
}
