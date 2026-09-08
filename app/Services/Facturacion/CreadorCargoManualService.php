<?php

namespace App\Services\Facturacion;

use App\Exceptions\CargoManualInvalidoException;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Inscripcion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreadorCargoManualService
{
    public const CONCEPTOS_RESERVADOS = ['INSCRIPCION', 'MENSUALIDAD', 'RECARGO', 'DESCUENTO'];

    public function crear(array $datos, ?int $usuarioId): Cargo
    {
        $importe = $this->normalizarImporte($datos['subtotal'] ?? $datos['importe'] ?? null);
        [$anio, $mes] = $this->normalizarPeriodo($datos['periodo_anio'] ?? null, $datos['periodo_mes'] ?? null);
        $emision = $this->fecha($datos['fecha_emision'] ?? null, 'fecha_emision', 'La fecha de emisión es obligatoria.');
        $vencimiento = $this->fecha($datos['fecha_vencimiento'] ?? null, 'fecha_vencimiento', 'La fecha de vencimiento es obligatoria.');

        if ($vencimiento->lt($emision)) {
            throw new CargoManualInvalidoException('La fecha de vencimiento debe ser igual o posterior a la fecha de emisión.', 'fecha_vencimiento');
        }

        return DB::transaction(function () use ($datos, $usuarioId, $importe, $anio, $mes, $emision, $vencimiento) {
            $inscripcion = Inscripcion::query()->with(['prospecto', 'cursos', 'grupo'])->find($datos['inscripciones_id'] ?? null);
            if (! $inscripcion || ! $inscripcion->prospecto || ! $inscripcion->cursos || ! $inscripcion->grupo) {
                throw new CargoManualInvalidoException('La inscripción seleccionada no existe o no conserva sus relaciones requeridas.', 'inscripciones_id');
            }

            $concepto = ConceptoCobro::query()->find($datos['concepto_cobro_id'] ?? null);
            if (! $concepto) {
                throw new CargoManualInvalidoException('El concepto de cobro seleccionado no existe.', 'concepto_cobro_id');
            }
            if (! $concepto->activo) {
                throw new CargoManualInvalidoException('El concepto de cobro seleccionado ya no está activo.', 'concepto_cobro_id');
            }
            if (in_array(strtoupper($concepto->clave), self::CONCEPTOS_RESERVADOS, true)) {
                throw new CargoManualInvalidoException('El concepto seleccionado está reservado para otro proceso de facturación.', 'concepto_cobro_id');
            }

            $cargo = new Cargo();
            $cargo->inscripciones_id = $inscripcion->getKey();
            $cargo->concepto_cobro_id = $concepto->getKey();
            $cargo->periodo_anio = $anio;
            $cargo->periodo_mes = $mes;
            $cargo->fecha_emision = $emision->format('Y-m-d');
            $cargo->fecha_vencimiento = $vencimiento->format('Y-m-d');
            $cargo->moneda = 'MXN';
            $cargo->subtotal = $importe;
            $cargo->descuento = '0.00';
            $cargo->recargo = '0.00';
            $cargo->impuestos = '0.00';
            $cargo->total = $importe;
            $cargo->saldo_pendiente = $importe;
            $cargo->estado = Cargo::ESTADO_PENDIENTE;
            $cargo->origen = Cargo::ORIGEN_MANUAL;
            $cargo->clave_idempotencia = null;
            $cargo->observaciones = $this->nullableString($datos['observaciones'] ?? null);
            $cargo->created_by = $usuarioId;
            $cargo->save();

            return $cargo;
        });
    }

    private function normalizarImporte($valor): string
    {
        if (! is_string($valor) && ! is_int($valor)) {
            throw new CargoManualInvalidoException('El importe debe usar un formato decimal válido.', 'subtotal');
        }
        $valor = (string) $valor;
        if (! preg_match('/^(?:0|[1-9]\d{0,9})(?:\.\d{1,2})?$/D', $valor)) {
            throw new CargoManualInvalidoException('El importe debe ser mayor que cero, sin separadores y con máximo dos decimales.', 'subtotal');
        }
        [$enteros, $decimales] = array_pad(explode('.', $valor, 2), 2, '');
        $normalizado = $enteros.'.'.str_pad($decimales, 2, '0');
        if ($normalizado === '0.00' || strlen($enteros) > 10 || (strlen($enteros) === 10 && strcmp($normalizado, '9999999999.99') > 0)) {
            throw new CargoManualInvalidoException('El importe debe estar entre 0.01 y 9,999,999,999.99.', 'subtotal');
        }
        return $normalizado;
    }

    private function normalizarPeriodo($anio, $mes): array
    {
        $anio = $anio === '' ? null : $anio;
        $mes = $mes === '' ? null : $mes;
        if (($anio === null) xor ($mes === null)) {
            throw new CargoManualInvalidoException('El año y el mes del periodo deben proporcionarse juntos.', $anio === null ? 'periodo_anio' : 'periodo_mes');
        }
        if ($anio === null) return [null, null];
        if (filter_var($anio, FILTER_VALIDATE_INT) === false || (int) $anio < 1 || (int) $anio > 65535) {
            throw new CargoManualInvalidoException('El año del periodo debe estar entre 1 y 65535.', 'periodo_anio');
        }
        if (filter_var($mes, FILTER_VALIDATE_INT) === false || (int) $mes < 1 || (int) $mes > 12) {
            throw new CargoManualInvalidoException('El mes del periodo debe estar entre 1 y 12.', 'periodo_mes');
        }
        return [(int) $anio, (int) $mes];
    }

    private function fecha($valor, string $campo, string $mensaje): CarbonImmutable
    {
        if (! is_string($valor) || $valor === '') throw new CargoManualInvalidoException($mensaje, $campo);
        try {
            $fecha = CarbonImmutable::createFromFormat('!Y-m-d', $valor);
        } catch (\InvalidArgumentException $exception) {
            throw new CargoManualInvalidoException('La fecha no tiene un formato válido.', $campo);
        }
        if (! $fecha || $fecha->format('Y-m-d') !== $valor) throw new CargoManualInvalidoException('La fecha no tiene un formato válido.', $campo);
        return $fecha;
    }

    private function nullableString($valor): ?string
    {
        if ($valor === null || trim((string) $valor) === '') return null;
        return trim((string) $valor);
    }
}
