<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Inscripcion extends Model
{
    // use HasFactory;
    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'inscripciones';

   protected $fillable = [
       'fecha_inscripcion',
       'prospectos_id',
       'cursos_id',
       'grupo_id',
       'estatus',
       'fecha_inicio',
       'fecha_fin',
       'moneda',
       'monto_inscripcion',
       'monto_mensualidad',
       'dia_vencimiento',
       'numero_mensualidades',
       'descuento',
       'beca',
       'observaciones_financieras',
       'responsable_pago_id',
   ];

   protected $casts = [
       'fecha_inscripcion' => 'date:Y-m-d',
       'fecha_inicio' => 'date:Y-m-d',
       'fecha_fin' => 'date:Y-m-d',
       'monto_inscripcion' => 'decimal:2',
       'monto_mensualidad' => 'decimal:2',
       'descuento' => 'decimal:2',
       'beca' => 'decimal:2',
       'dia_vencimiento' => 'integer',
       'numero_mensualidades' => 'integer',
   ];

   /**
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'inscripciones_id';

    protected static function booted()
    {
        static::creating(function (Inscripcion $inscripcion) {
            if (Auth::check()) {
                $inscripcion->created_by = Auth::id();
                $inscripcion->updated_by = Auth::id();
            }
        });

        static::updating(function (Inscripcion $inscripcion) {
            if (Auth::check()) {
                $inscripcion->updated_by = Auth::id();
            }
        });
    }

    public function prospecto()
    {
        return $this->belongsTo(Prospecto::class,'prospectos_id','prospectos_id');
    }

    public function cursos()
    {
        return $this->belongsTo(Curso::class,'cursos_id','cursos_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class,'grupo_id','grupo_id');
    }

    public function responsablePago()
    {
        return $this->belongsTo(ResponsablePago::class, 'responsable_pago_id', 'responsable_pago_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

}
