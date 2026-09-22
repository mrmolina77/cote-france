<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditoriaPago extends Model
{
    public const CREAR = 'crear';
    public const MODIFICAR = 'modificar';
    public const CONFIRMAR = 'confirmar';
    public const CANCELAR = 'cancelar';
    public const REEMBOLSAR = 'reembolsar';
    public const GENERAR_RECIBO = 'generar_recibo';
    public const REGENERAR_RECIBO = 'regenerar_recibo';
    public const ENVIAR_CORREO = 'enviar_correo';
    public const REENVIAR_CORREO = 'reenviar_correo';
    public const CORREO_ENVIADO = 'correo_enviado';
    public const CORREO_FALLIDO = 'correo_fallido';
    public const CORREO_OMITIDO = 'correo_omitido';
    public const ACCIONES = [self::CREAR, self::MODIFICAR, self::CONFIRMAR, self::CANCELAR, self::REEMBOLSAR,
        self::GENERAR_RECIBO, self::REGENERAR_RECIBO, self::ENVIAR_CORREO, self::REENVIAR_CORREO,
        self::CORREO_ENVIADO, self::CORREO_FALLIDO, self::CORREO_OMITIDO];

    protected $table = 'auditoria_pagos';
    protected $primaryKey = 'auditoria_pago_id';
    protected $guarded = ['*'];
    protected $casts = ['pago_id'=>'integer', 'usuario_id'=>'integer', 'ocurrido_en'=>'datetime',
        'valores_anteriores'=>'array', 'valores_nuevos'=>'array', 'metadatos'=>'array'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('La auditoría financiera es inmutable.'));
        static::deleting(fn () => throw new LogicException('La auditoría financiera es append-only.'));
    }

    /**
     * Eloquent no dispara el evento "updating" al guardar un modelo existente
     * sin atributos sucios. La bitácora es append-only incluso en ese caso.
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new LogicException('La auditoría financiera es inmutable.');
        }

        return parent::save($options);
    }

    public function pago() { return $this->belongsTo(Pago::class, 'pago_id', 'pago_id'); }
    public function usuario() { return $this->belongsTo(User::class, 'usuario_id'); }
    public function scopeDelPago(Builder $q, int $id): Builder { return $q->where('pago_id', $id); }
    public function scopeConAccion(Builder $q, string $accion): Builder { return $q->where('accion', $accion); }
    public function scopeDelUsuario(Builder $q, int $id): Builder { return $q->where('usuario_id', $id); }
    public function scopeEntreFechas(Builder $q, $desde, $hasta): Builder { return $q->whereBetween('ocurrido_en', [$desde, $hasta]); }
}
