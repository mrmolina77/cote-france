<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponsablePago extends Model
{
    protected $table = 'responsables_pago';

    protected $primaryKey = 'responsable_pago_id';

    protected $fillable = [
        'tipo',
        'prospectos_id',
        'nombre_razon_social',
        'telefono',
        'correo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public static function activeForProspect(Prospecto $prospecto): self
    {
        return static::where('prospectos_id', $prospecto->getKey())->where('activo', true)->first()
            ?: static::create([
                'tipo' => 'persona',
                'prospectos_id' => $prospecto->getKey(),
                'nombre_razon_social' => trim($prospecto->prospectos_nombres.' '.$prospecto->prospectos_apellidos),
                'telefono' => $prospecto->prospectos_telefono1,
                'correo' => $prospecto->prospectos_correo,
                'activo' => true,
            ]);
    }

    public function prospecto()
    {
        return $this->belongsTo(Prospecto::class, 'prospectos_id', 'prospectos_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'responsable_pago_id', 'responsable_pago_id');
    }
}
