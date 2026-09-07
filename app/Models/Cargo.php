<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    public const ORIGEN_MANUAL = 'manual';
    public const ORIGEN_AUTOMATICO = 'automatico';

    public const ORIGENES = [
        self::ORIGEN_MANUAL,
        self::ORIGEN_AUTOMATICO,
    ];

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PARCIAL = 'parcial';
    public const ESTADO_PAGADO = 'pagado';
    public const ESTADO_VENCIDO = 'vencido';
    public const ESTADO_CANCELADO = 'cancelado';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_PARCIAL,
        self::ESTADO_PAGADO,
        self::ESTADO_VENCIDO,
        self::ESTADO_CANCELADO,
    ];

    protected $table = 'cargos';

    protected $primaryKey = 'cargo_id';

    protected $fillable = [
        'inscripciones_id',
        'concepto_cobro_id',
        'periodo_anio',
        'periodo_mes',
        'fecha_emision',
        'fecha_vencimiento',
        'moneda',
        'subtotal',
        'descuento',
        'recargo',
        'impuestos',
        'total',
        'saldo_pendiente',
        'estado',
        'origen',
        'clave_idempotencia',
        'observaciones',
    ];

    protected $casts = [
        'fecha_emision' => 'date:Y-m-d',
        'fecha_vencimiento' => 'date:Y-m-d',
        'periodo_anio' => 'integer',
        'periodo_mes' => 'integer',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'recargo' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'total' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripciones_id', 'inscripciones_id');
    }

    public function conceptoCobro()
    {
        return $this->belongsTo(ConceptoCobro::class, 'concepto_cobro_id', 'concepto_cobro_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeVencidos(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_VENCIDO);
    }

    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->whereIn('estado', [
            self::ESTADO_PENDIENTE,
            self::ESTADO_PARCIAL,
            self::ESTADO_VENCIDO,
        ]);
    }
}
