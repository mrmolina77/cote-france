<?php

namespace App\Services\Facturacion;

use App\Models\MetodoPago;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MetodoPagoBehaviorService
{
    /** Única fuente de metadatos para los datos variables de un pago. */
    public const CAMPOS = [
        'requiere_forma_pago_sat' => ['campo' => 'forma_pago_sat', 'etiqueta' => 'Forma de pago SAT', 'control' => 'text', 'maximo' => 2, 'reglas' => ['string', 'regex:/^\d{2}$/D'], 'nullable' => false],
        'requiere_banco' => ['campo' => 'banco', 'etiqueta' => 'Banco', 'control' => 'text', 'maximo' => 120, 'reglas' => ['string', 'max:120'], 'nullable' => false],
        'requiere_referencia' => ['campo' => 'referencia', 'etiqueta' => 'Referencia', 'control' => 'text', 'maximo' => 120, 'reglas' => ['string', 'max:120'], 'nullable' => false],
        'requiere_numero_cheque' => ['campo' => 'numero_cheque', 'etiqueta' => 'Número de cheque', 'control' => 'text', 'maximo' => 50, 'reglas' => ['string', 'max:50'], 'nullable' => false],
        'requiere_rastreo_spei' => ['campo' => 'rastreo_spei', 'etiqueta' => 'Rastreo SPEI', 'control' => 'text', 'maximo' => 100, 'reglas' => ['string', 'max:100'], 'nullable' => false],
        'requiere_autorizacion' => ['campo' => 'numero_autorizacion', 'etiqueta' => 'Autorización', 'control' => 'text', 'maximo' => 100, 'reglas' => ['string', 'max:100'], 'nullable' => false],
        'requiere_terminal' => ['campo' => 'terminal', 'etiqueta' => 'Terminal', 'control' => 'text', 'maximo' => 100, 'reglas' => ['string', 'max:100'], 'nullable' => false],
        'requiere_ultimos_4_digitos' => ['campo' => 'ultimos_4_digitos', 'etiqueta' => 'Últimos 4 dígitos', 'control' => 'text', 'maximo' => 4, 'reglas' => ['string', 'regex:/^\d{4}$/D'], 'nullable' => false],
        'requiere_proveedor' => ['campo' => 'proveedor', 'etiqueta' => 'Proveedor', 'control' => 'text', 'maximo' => 120, 'reglas' => ['string', 'max:120'], 'nullable' => false],
        'requiere_anticipo_relacionado' => ['campo' => 'anticipo_relacionado_id', 'etiqueta' => 'Anticipo relacionado', 'control' => 'number', 'maximo' => null, 'reglas' => ['integer', 'min:1'], 'nullable' => false],
        'requiere_comprobante' => ['campo' => 'comprobante', 'etiqueta' => 'Comprobante', 'control' => 'file', 'maximo' => null, 'reglas' => [], 'nullable' => false],
    ];

    public function catalogoCampos(): array
    {
        return self::CAMPOS;
    }

    public function metodosDisponibles()
    {
        return MetodoPago::query()->activos()->ordenados()->get();
    }

    public function seleccionarActivo($metodoPagoId): MetodoPago
    {
        $validator = Validator::make(['metodo_pago_id' => $metodoPagoId], ['metodo_pago_id' => ['required', 'integer', 'min:1']]);
        if ($validator->fails()) {
            throw ValidationException::withMessages(['metodo_pago_id' => 'El método de pago seleccionado no es válido.']);
        }

        $metodo = MetodoPago::query()->activos()->find($metodoPagoId);
        if ($metodo === null) {
            throw ValidationException::withMessages(['metodo_pago_id' => 'El método de pago seleccionado no existe o está inactivo.']);
        }

        return $metodo;
    }

    public function configuracion(MetodoPago $metodo): array
    {
        return [
            'metodo_pago_id' => $metodo->getKey(),
            'activo' => $metodo->activo,
            'forma_pago_sat_fija' => $metodo->tieneFormaPagoSatFija() ? $metodo->clave_forma_pago_sat : null,
            'campos' => $this->camposAplicables($metodo),
            'reglas' => $this->reglasValidacion($metodo),
            'etiquetas' => $this->etiquetas($metodo),
        ];
    }

    public function camposAplicables(MetodoPago $metodo): array
    {
        $campos = [];
        foreach (self::CAMPOS as $indicador => $metadata) {
            if ($metodo->{$indicador} && ! ($indicador === 'requiere_forma_pago_sat' && $metodo->tieneFormaPagoSatFija())) {
                $campos[$metadata['campo']] = ['indicador' => $indicador, 'requerido' => true] + $metadata;
            }
        }
        return $campos;
    }

    public function camposObligatorios(MetodoPago $metodo): array
    {
        return array_keys($this->camposAplicables($metodo));
    }

    public function reglasValidacion(MetodoPago $metodo): array
    {
        $reglas = [];
        foreach ($this->camposAplicables($metodo) as $campo => $metadata) {
            $reglas[$campo] = array_merge(['required'], $metadata['reglas']);
        }
        return $reglas;
    }

    public function etiquetas(MetodoPago $metodo): array
    {
        return array_map(fn (array $metadata) => $metadata['etiqueta'], $this->camposAplicables($metodo));
    }

    public function normalizarDatos(MetodoPago $metodo, array $datos): array
    {
        $normalizados = [];
        foreach ($this->camposAplicables($metodo) as $campo => $metadata) {
            if (! array_key_exists($campo, $datos)) continue;
            $valor = $datos[$campo];
            $normalizados[$campo] = is_string($valor) ? (trim($valor) === '' ? null : trim($valor)) : $valor;
        }
        return $normalizados;
    }

    public function resolverFormaPagoSat(MetodoPago $metodo, $formaCapturada = null): ?string
    {
        if ($metodo->tieneFormaPagoSatFija()) return $metodo->clave_forma_pago_sat;
        if (! $metodo->requiere_forma_pago_sat) return null;

        return Validator::make(
            ['forma_pago_sat' => $formaCapturada],
            ['forma_pago_sat' => $this->reglasValidacion($metodo)['forma_pago_sat']]
        )->validate()['forma_pago_sat'];
    }
}
