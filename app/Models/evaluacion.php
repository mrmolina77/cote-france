<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class evaluacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones';

    protected $primaryKey = 'evaluacion_id';

    protected $fillable = [
        'prospectos_id',
        'horarios_id',
        'asistio',
        'calificacion'
    ];

    public function prospecto()
    {
        return $this->belongsTo(Prospecto::class, 'prospectos_id', 'prospectos_id');
    }
}
