<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloqueosProfesores extends Model
{
    use HasFactory;

    protected $table = 'bloqueos_profesores';
    protected $primaryKey = 'bloqueo_id';
    public $timestamps = true;

    protected $fillable = [
        'profesor_id',
        'dias_id',
        'horas_id',
        'fecha',
        'motivo',
    ];

    // Relaciones
    public function profesor()
    {
        return $this->belongsTo(Profesor::class, 'profesor_id', 'profesores_id');
    }

    public function dia()
    {
        return $this->belongsTo(Dia::class, 'dias_id', 'dias_id');
    }

    public function hora()
    {
        return $this->belongsTo(Hora::class, 'horas_id', 'horas_id');
    }

    /**
     * Scope a query to only include active blocks for a given professor, date, and hour.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $profesorId
     * @param  \Carbon\Carbon  $fechaCarbon
     * @param  int  $horaId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsBlocked($query, $profesorId, \Carbon\Carbon $fechaCarbon, $horaId)
    {
        $dayOfWeek = $fechaCarbon->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
        return $query->where('profesor_id', $profesorId)
            ->where(function ($q) use ($fechaCarbon, $dayOfWeek, $horaId) {
                // Full day block
                $q->where(function ($q_full) use ($fechaCarbon) {
                    $q_full->whereNotNull('fecha')->where('fecha', $fechaCarbon->toDateString());
                }) // Recurring block for specific day and hour
                ->orWhere(function ($q_rec) use ($dayOfWeek, $horaId) {
                    $q_rec->whereNull('fecha')->where('dias_id', $dayOfWeek)->where('horas_id', $horaId);
                });
            });
    }

}
