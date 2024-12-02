<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GruposDetalles extends Model
{
    // use HasFactory;

    protected $table = 'grupos_detalles';

    protected $fillable = ['grupo_id','horas_id','profesores_id','dias_id'];

    protected $primaryKey = 'detalles_id';

    public function dia()
    {
        return $this->belongsTo(Dia::class,'dias_id','dias_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class,'grupo_id','grupo_id');
    }

    public function hora()
    {
        return $this->belongsTo(Hora::class,'horas_id','horas_id');
    }

    public function profesor()
    {
        return $this->belongsTo(Profesor::class,'profesores_id','profesores_id');
    }





}
