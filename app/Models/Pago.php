<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_CONFIRMADO = 'confirmado';
    public const ESTADO_CANCELADO = 'cancelado';
    public const ESTADO_REEMBOLSADO = 'reembolsado';

    public const ESTADOS = [
        self::ESTADO_BORRADOR,
        self::ESTADO_CONFIRMADO,
        self::ESTADO_CANCELADO,
        self::ESTADO_REEMBOLSADO,
    ];

    protected $table = 'pagos';

    protected $primaryKey = 'pago_id';

    protected $fillable = [
        'folio', 'inscripciones_id', 'prospectos_id', 'responsable_pago_id',
        'fecha_pago', 'zona_horaria', 'moneda', 'tipo_cambio', 'monto',
        'metodo_pago_id', 'forma_pago_sat', 'banco', 'referencia',
        'numero_cheque', 'rastreo_spei', 'numero_autorizacion', 'terminal',
        'ultimos_4_digitos', 'proveedor', 'anticipo_relacionado_id',
        'identificador_transaccion_externa', 'fecha_movimiento', 'observaciones',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'fecha_movimiento' => 'datetime',
        'fecha_confirmacion' => 'datetime',
        'fecha_cancelacion' => 'datetime',
        'fecha_reembolso' => 'datetime',
        'monto' => 'decimal:2',
        'tipo_cambio' => 'decimal:6',
    ];

    public function inscripcion() { return $this->belongsTo(Inscripcion::class, 'inscripciones_id', 'inscripciones_id'); }
    public function prospecto() { return $this->belongsTo(Prospecto::class, 'prospectos_id', 'prospectos_id'); }
    public function responsablePago() { return $this->belongsTo(ResponsablePago::class, 'responsable_pago_id', 'responsable_pago_id'); }
    public function metodoPago() { return $this->belongsTo(MetodoPago::class, 'metodo_pago_id', 'metodo_pago_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by', 'id'); }
    public function confirmedBy() { return $this->belongsTo(User::class, 'confirmed_by', 'id'); }
    public function cancelledBy() { return $this->belongsTo(User::class, 'cancelled_by', 'id'); }
    public function anticipoRelacionado() { return $this->belongsTo(self::class, 'anticipo_relacionado_id', 'pago_id'); }
    public function pagosRelacionadosComoAnticipo() { return $this->hasMany(self::class, 'anticipo_relacionado_id', 'pago_id'); }

    public function scopeBorradores(Builder $query): Builder { return $query->where('estado', self::ESTADO_BORRADOR); }
    public function scopeConfirmados(Builder $query): Builder { return $query->where('estado', self::ESTADO_CONFIRMADO); }
    public function scopeCancelados(Builder $query): Builder { return $query->where('estado', self::ESTADO_CANCELADO); }
    public function scopeReembolsados(Builder $query): Builder { return $query->where('estado', self::ESTADO_REEMBOLSADO); }
    public function scopeDelAlumno(Builder $query, $prospectoId): Builder { return $query->where('prospectos_id', $prospectoId); }
    public function scopeDeLaInscripcion(Builder $query, $inscripcionId): Builder { return $query->where('inscripciones_id', $inscripcionId); }
}
