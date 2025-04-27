<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capitulo extends Model
{
    use HasFactory;
    protected $table = 'capitulos';

    protected $primaryKey = 'capitulo_id';

    public function getFullNameAttribute()
    {
    return $this->capitulo_descripcion . ' ' . $this->capitulo_codigo;
    }
}
