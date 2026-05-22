<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClasePrueba extends Model
{
    use SoftDeletes;

    protected $table = 'clases_prueba';

    protected $primaryKey = 'clase_prueba_id';

    protected $fillable = [
        'prospectos_id',
        'grupo_id',
        'horarios_id',
        'horarios_dia',
        'horas_id',
        'profesores_id',
        'espacios_id',
        'modalidad_id',
        'asistio',
        'observacion',
        'estado',
        'validado',
    ];

    protected $casts = [
        'horarios_dia' => 'date',
        'asistio' => 'integer',
        'validado' => 'boolean',
    ];

    public function prospecto()
    {
        return $this->belongsTo(Prospecto::class, 'prospectos_id', 'prospectos_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id', 'grupo_id');
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class, 'horarios_id', 'horarios_id');
    }

    public function hora()
    {
        return $this->belongsTo(Hora::class, 'horas_id', 'horas_id');
    }

    public function profesor()
    {
        return $this->belongsTo(Profesor::class, 'profesores_id', 'profesores_id');
    }

    public function espacio()
    {
        return $this->belongsTo(Espacio::class, 'espacios_id', 'espacios_id');
    }

    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id', 'modalidad_id');
    }
}
