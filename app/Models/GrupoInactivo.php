<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoInactivo extends Model
{
    protected $table = 'grupos_inactivos';

    protected $primaryKey = 'grupo_inactivo_id';

    protected $fillable = [
        'grupo_id',
        'fecha',
        'horas_id',
        'modalidad_id',
    ];
}
