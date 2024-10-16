<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    // use HasFactory;

    protected $table = 'estados';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'estado_id';
}
