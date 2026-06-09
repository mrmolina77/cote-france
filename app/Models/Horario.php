<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Horario extends Model
{
    // use HasFactory;
    use SoftDeletes;
    /**
    * The table associated with the model.
    *
    * @var string
    */
    protected $table = 'horarios';

    protected $fillable = ['espacios_id','horarios_dia','horas_id','grupo_id','profesores_id','origen','protegido','protegido_at'];

    protected $casts = [
        'protegido' => 'boolean',
        'protegido_at' => 'datetime',
    ];

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
    protected $primaryKey = 'horarios_id';

    public function hora()
    {
        return $this->belongsTo(Hora::class,'horas_id','horas_id');
    }

    public function espacio()
    {
        return $this->belongsTo(Espacio::class,'espacios_id','espacios_id');
    }
    public function profesor()
    {
        return $this->belongsTo(Profesor::class,'profesores_id','profesores_id');
    }
    public function grupo()
    {
        return $this->belongsTo(Grupo::class,'grupo_id','grupo_id');
    }
    public function diario()
{
    return $this->hasOne(Diario::class, 'horarios_id', 'horarios_id');
}
}
