<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ConceptoCobro extends Model
{
    protected $table = 'conceptos_cobro';

    protected $primaryKey = 'concepto_cobro_id';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'clave_producto_servicio_sat',
        'clave_unidad_sat',
        'objeto_impuesto_sat',
        'tasa_iva',
        'activo',
        'orden',
    ];

    protected $casts = [
        'tasa_iva' => 'decimal:6',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}
