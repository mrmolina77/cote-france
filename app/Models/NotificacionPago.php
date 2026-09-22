<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificacionPago extends Model
{
    public const TIPO_RECIBIDO = 'pago_recibido';
    public const TIPO_CANCELADO = 'pago_cancelado';
    public const SOLICITUD_INICIAL = 'inicial';
    public const SOLICITUD_REENVIO = 'reenvio';
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PROCESANDO = 'procesando';
    public const ESTADO_ENVIADO = 'enviado';
    public const ESTADO_FALLIDO = 'fallido';
    public const ESTADO_OMITIDO = 'omitido';

    protected $table = 'notificaciones_pago';
    protected $primaryKey = 'notificacion_pago_id';
    protected $guarded = ['*'];
    protected $casts = [
        'intentos' => 'integer', 'programado_en' => 'datetime', 'iniciado_en' => 'datetime',
        'enviado_en' => 'datetime', 'ultimo_intento_en' => 'datetime', 'solicitado_en' => 'datetime',
    ];

    public function pago() { return $this->belongsTo(Pago::class, 'pago_id', 'pago_id'); }
    public function comprobantePago() { return $this->belongsTo(ComprobantePago::class, 'comprobante_pago_id', 'comprobante_pago_id'); }
    public function solicitadoPor() { return $this->belongsTo(User::class, 'solicitado_por', 'id'); }
}
