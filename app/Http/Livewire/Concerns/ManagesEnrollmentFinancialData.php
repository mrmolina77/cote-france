<?php

namespace App\Http\Livewire\Concerns;

use App\Models\Prospecto;
use App\Models\ResponsablePago;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

trait ManagesEnrollmentFinancialData
{
    public $responsable_opcion = 'alumno';
    public $responsable_pago_id;
    public $responsable_tipo = 'persona';
    public $responsable_nombre;
    public $responsable_telefono;
    public $responsable_correo;

    protected function financialRules(string $prefix = '', bool $allowConservar = true): array
    {
        return [
            $prefix.'estatus' => 'nullable|in:activa,suspendida,finalizada,cancelada',
            $prefix.'fecha_inicio' => 'nullable|date',
            $prefix.'fecha_fin' => 'nullable|date|after_or_equal:'.$prefix.'fecha_inicio',
            $prefix.'moneda' => 'nullable|in:MXN',
            $prefix.'monto_inscripcion' => 'nullable|numeric|min:0|max:9999999999.99',
            $prefix.'monto_mensualidad' => 'nullable|numeric|min:0|max:9999999999.99',
            $prefix.'dia_vencimiento' => ['nullable', Rule::requiredIf(fn () => (float) data_get($this, $prefix.'monto_mensualidad') > 0), 'integer', 'between:1,31'],
            $prefix.'numero_mensualidades' => ['nullable', Rule::requiredIf(fn () => (float) data_get($this, $prefix.'monto_mensualidad') > 0), 'integer', 'min:1', 'max:120'],
            $prefix.'descuento' => 'nullable|numeric|between:0,100',
            $prefix.'beca' => 'nullable|numeric|between:0,100',
            $prefix.'observaciones_financieras' => 'nullable|string|max:2000',
            'responsable_opcion' => 'required|in:'.($allowConservar ? 'conservar,' : '').'alumno,existente,nuevo',
            'responsable_pago_id' => 'nullable|required_if:responsable_opcion,existente|exists:responsables_pago,responsable_pago_id',
            'responsable_tipo' => 'nullable|required_if:responsable_opcion,nuevo|in:persona,empresa',
            'responsable_nombre' => 'nullable|required_if:responsable_opcion,nuevo|string|max:255',
            'responsable_telefono' => 'nullable|string|max:80',
            'responsable_correo' => 'nullable|email|max:255',
        ];
    }

    protected function validateFinancialCombination(string $prefix = ''): void
    {
        $descuento = $this->blankToNull(data_get($this, $prefix.'descuento')) ?? 0;
        $beca = $this->blankToNull(data_get($this, $prefix.'beca')) ?? 0;

        if ((float) $descuento + (float) $beca > 100) {
            throw ValidationException::withMessages([
                $prefix.'beca' => 'La suma del descuento y la beca no puede ser mayor a 100%.',
            ]);
        }
    }

    protected function resolveResponsable(int $prospectoId): ?ResponsablePago
    {
        if ($this->responsable_opcion === 'conservar') return null;
        if ($this->responsable_opcion === 'existente') {
            $responsable = ResponsablePago::find($this->responsable_pago_id);
            if (! $responsable || ! $responsable->activo) {
                throw ValidationException::withMessages(['responsable_pago_id' => 'El responsable seleccionado no está activo.']);
            }
            return $responsable;
        }

        if ($this->responsable_opcion === 'nuevo') {
            return ResponsablePago::create([
                'tipo' => $this->responsable_tipo,
                'prospectos_id' => null,
                'nombre_razon_social' => $this->responsable_nombre,
                'telefono' => $this->blankToNull($this->responsable_telefono),
                'correo' => $this->blankToNull($this->responsable_correo),
                'activo' => true,
            ]);
        }

        return ResponsablePago::activeForProspect(Prospecto::findOrFail($prospectoId));
    }

    protected function blankToNull($value)
    {
        return $value === '' ? null : $value;
    }
}
