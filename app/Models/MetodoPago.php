<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    public const EFECTIVO = 'EFECTIVO';
    public const CHEQUE_NOMINATIVO = 'CHEQUE_NOMINATIVO';
    public const TRANSFERENCIA_SPEI = 'TRANSFERENCIA_SPEI';
    public const TARJETA_CREDITO = 'TARJETA_CREDITO';
    public const MONEDERO_ELECTRONICO = 'MONEDERO_ELECTRONICO';
    public const DINERO_ELECTRONICO = 'DINERO_ELECTRONICO';
    public const TARJETA_DEBITO = 'TARJETA_DEBITO';
    public const TARJETA_SERVICIOS = 'TARJETA_SERVICIOS';
    public const APLICACION_ANTICIPO = 'APLICACION_ANTICIPO';
    public const INTERMEDIARIO_PAGOS = 'INTERMEDIARIO_PAGOS';
    public const POR_DEFINIR = 'POR_DEFINIR';
    public const DEPOSITO_BANCARIO = 'DEPOSITO_BANCARIO';

    protected $table = 'metodos_pago';

    protected $primaryKey = 'metodo_pago_id';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'clave_forma_pago_sat',
        'requiere_forma_pago_sat',
        'requiere_banco',
        'requiere_referencia',
        'requiere_numero_cheque',
        'requiere_rastreo_spei',
        'requiere_autorizacion',
        'requiere_terminal',
        'requiere_ultimos_4_digitos',
        'requiere_proveedor',
        'requiere_anticipo_relacionado',
        'requiere_comprobante',
        'activo',
        'orden',
    ];

    protected $casts = [
        'requiere_forma_pago_sat' => 'boolean',
        'requiere_banco' => 'boolean',
        'requiere_referencia' => 'boolean',
        'requiere_numero_cheque' => 'boolean',
        'requiere_rastreo_spei' => 'boolean',
        'requiere_autorizacion' => 'boolean',
        'requiere_terminal' => 'boolean',
        'requiere_ultimos_4_digitos' => 'boolean',
        'requiere_proveedor' => 'boolean',
        'requiere_anticipo_relacionado' => 'boolean',
        'requiere_comprobante' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'metodo_pago_id', 'metodo_pago_id');
    }

    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    public function tieneFormaPagoSatFija(): bool
    {
        return is_string($this->clave_forma_pago_sat)
            && preg_match('/^\d{2}$/D', $this->clave_forma_pago_sat) === 1;
    }
}
