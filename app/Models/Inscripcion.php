<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Inscripcion extends Model
{
    use SoftDeletes;
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

   protected $primaryKey = 'inscripciones_id';

    public function setAttribute($key, $value)
    {
        if (is_string($value) && trim($value) === '' && $key !== 'moneda') {
            $value = null;
        }

        return parent::setAttribute($key, $value);
    }

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

    /** The canonical, queryable definition of a completed financial setup. */
    public function scopeFinancieramenteConfiguradas(Builder $query): Builder
    {
        return $query->whereNotNull($query->qualifyColumn('estatus'))
            ->whereNotNull($query->qualifyColumn('fecha_inicio'))
            ->where($query->qualifyColumn('moneda'), 'MXN')
            ->whereNotNull($query->qualifyColumn('monto_inscripcion'))
            ->whereNotNull($query->qualifyColumn('monto_mensualidad'))
            ->whereNotNull($query->qualifyColumn('descuento'))
            ->whereNotNull($query->qualifyColumn('beca'))
            ->whereColumn($query->qualifyColumn('descuento'), '<=', DB::raw('100 - '.$query->qualifyColumn('beca')))
            ->whereHas('responsablePago', fn (Builder $responsables) => $responsables->where('activo', true))
            ->where(function (Builder $monthly) use ($query) {
                $monthly->where($query->qualifyColumn('monto_mensualidad'), '=', 0)
                    ->orWhere(function (Builder $positive) use ($query) {
                        $positive->where($query->qualifyColumn('monto_mensualidad'), '>', 0)
                            ->whereBetween($query->qualifyColumn('numero_mensualidades'), [1, 120])
                            ->whereBetween($query->qualifyColumn('dia_vencimiento'), [1, 31]);
                    });
            });
    }

    public function scopeFinancieramentePendientes(Builder $query): Builder
    {
        return $query->whereNotIn(
            $query->qualifyColumn($this->getKeyName()),
            static::query()->financieramenteConfiguradas()->select($this->qualifyColumn($this->getKeyName()))
        );
    }

    public function getFinancieramenteConfiguradaAttribute(): bool
    {
        return static::query()->whereKey($this->getKey())->financieramenteConfiguradas()->exists();
    }

    public function getEstadoConfiguracionFinancieraAttribute(): string
    {
        return $this->financieramente_configurada ? 'configurada' : 'pendiente';
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
