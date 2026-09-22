<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ComprobantePago extends Model
{
    public const MIME_PDF = 'application/pdf';
    public const DISCO_PRIVADO = 'local';
    public const DIRECTORIO = 'comprobantes_pago';

    protected $table = 'comprobantes_pago';
    protected $primaryKey = 'comprobante_pago_id';
    protected $guarded = ['*'];

    protected $casts = [
        'pago_id' => 'integer',
        'anio_folio' => 'integer',
        'secuencia_folio' => 'integer',
        'tamano_bytes' => 'integer',
        'generado_en' => 'datetime',
        'generado_por' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new LogicException('Los comprobantes internos no pueden eliminarse; deben conservarse para auditoría.');
        });
    }

    public function pago() { return $this->belongsTo(Pago::class, 'pago_id', 'pago_id'); }
    public function generadoPor() { return $this->belongsTo(User::class, 'generado_por', 'id'); }
    public function notificacionesPago() { return $this->hasMany(NotificacionPago::class, 'comprobante_pago_id', 'comprobante_pago_id'); }
}
