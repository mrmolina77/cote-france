<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoAplicacion extends Model
{
    protected $table = 'pago_aplicaciones';

    protected $primaryKey = 'pago_aplicacion_id';

    // Las aplicaciones son asientos creados exclusivamente por el servicio de dominio.
    protected $guarded = ['*'];

    protected $casts = [
        'importe_aplicado' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_posterior' => 'decimal:2',
    ];

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'pago_id', 'pago_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id', 'cargo_id');
    }
}
