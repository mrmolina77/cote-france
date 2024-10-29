<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    // use HasFactory;
    /**
    * The table associated with the model.
    *
    * @var string
    */
    protected $table = 'horarios';

    protected $fillable = ['espacios_id','horarios_dia','horas_id','grupos_id'];

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
    protected $primaryKey = 'horarios_id';

    public function hora()
    {
        return $this->belongsTo(Profesor::class,'horas_id','horas_id');
    }

    public function espacio()
    {
        return $this->belongsTo(Espacio::class,'espacios_id','espacios_id');
    }
    public function grupo()
    {
        return $this->belongsTo(Grupo::class,'grupo_id','grupo_id');
    }
}
