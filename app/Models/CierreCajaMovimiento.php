<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class CierreCajaMovimiento extends Model
{
    protected $table = 'cierre_caja_movimientos';
    protected $guarded = ['cierre_caja_movimiento_id'];
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('El snapshot de un cierre de caja es inmutable.'));
        static::deleting(fn () => throw new LogicException('El snapshot de un cierre de caja es inmutable.'));
    }

    public function cierre() { return $this->belongsTo(CierreCaja::class, 'cierre_caja_id'); }
}
