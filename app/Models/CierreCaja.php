<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CierreCaja extends Model
{
    protected $table = 'cierres_caja';
    protected $primaryKey = 'cierre_caja_id';
    protected $guarded = ['cierre_caja_id'];
    protected $casts = [
        'fecha_operacion' => 'date:Y-m-d', 'ventana_inicio' => 'datetime', 'ventana_fin' => 'datetime',
        'cerrado_en' => 'datetime', 'totales_esperados' => 'array', 'importes_contados' => 'array',
        'diferencias' => 'array', 'snapshot_movimientos' => 'array',
    ];

    public function cajero() { return $this->belongsTo(User::class, 'cajero_id'); }
    public function cerradoPor() { return $this->belongsTo(User::class, 'cerrado_por'); }
}
