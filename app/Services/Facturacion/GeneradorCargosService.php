<?php

namespace App\Services\Facturacion;

use App\Exceptions\InscripcionFinancieraInvalidaException;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Inscripcion;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeneradorCargosService
{
    /**
     * Genera idempotentemente la inscripción y todo su calendario en una operación atómica.
     *
     * @return Collection<int, Cargo>
     * @throws InscripcionFinancieraInvalidaException
     */
    public function generarParaInscripcion(Inscripcion $inscripcion, ?int $usuarioId = null): Collection
    {
        return DB::transaction(function () use ($inscripcion, $usuarioId) {
            $inscripcion = $this->recargarYValidar($inscripcion, true);
            $cargos = collect();
            $cargoInscripcion = $this->crearCargoInscripcion($inscripcion, $usuarioId);

            if ($cargoInscripcion !== null) {
                $cargos->push($cargoInscripcion);
            }

            return $cargos->concat($this->crearMensualidades($inscripcion, $usuarioId))->values();
        });
    }

    /**
     * Genera o recupera idempotentemente el cargo de inscripción; retorna null si su importe es cero.
     *
     * @throws InscripcionFinancieraInvalidaException
     */
    public function generarCargoInscripcion(Inscripcion $inscripcion, ?int $usuarioId = null): ?Cargo
    {
        return DB::transaction(function () use ($inscripcion, $usuarioId) {
            return $this->crearCargoInscripcion($this->recargarYValidar($inscripcion, true), $usuarioId);
        });
    }

    /**
     * Genera o recupera idempotentemente las mensualidades permitidas por fecha_fin.
     *
     * @return Collection<int, Cargo>
     * @throws InscripcionFinancieraInvalidaException
     */
    public function generarMensualidades(Inscripcion $inscripcion, ?int $usuarioId = null): Collection
    {
        return DB::transaction(function () use ($inscripcion, $usuarioId) {
            return $this->crearMensualidades($this->recargarYValidar($inscripcion, true), $usuarioId);
        });
    }

    private function recargarYValidar(Inscripcion $inscripcion, bool $bloquear): Inscripcion
    {
        if (! $inscripcion->exists || $inscripcion->getKey() === null || $inscripcion->trashed()) {
            throw new InscripcionFinancieraInvalidaException('La inscripción no existe o fue eliminada.');
        }

        $query = Inscripcion::query()->whereKey($inscripcion->getKey());
        if ($bloquear && DB::connection()->getDriverName() !== 'sqlite') {
            $query->lockForUpdate();
        }

        $actual = $query->first();
        if ($actual === null || ! Inscripcion::query()->whereKey($actual->getKey())->financieramenteConfiguradas()->exists()) {
            throw new InscripcionFinancieraInvalidaException('La inscripción no tiene una configuración financiera válida.');
        }

        return $actual;
    }

    private function crearCargoInscripcion(Inscripcion $inscripcion, ?int $usuarioId): ?Cargo
    {
        if (! $this->esPositivo($inscripcion->monto_inscripcion)) {
            return null;
        }
        if ($inscripcion->fecha_inscripcion === null) {
            throw new InscripcionFinancieraInvalidaException('La fecha de inscripción es obligatoria para generar su cargo.');
        }

        $concepto = $this->conceptoActivo('INSCRIPCION');
        $importe = $inscripcion->monto_inscripcion;

        return $this->primeroOCrear('INSCRIPCION:'.$inscripcion->getKey(), [
            'inscripciones_id' => $inscripcion->getKey(),
            'concepto_cobro_id' => $concepto->getKey(),
            'periodo_anio' => null,
            'periodo_mes' => null,
            'fecha_emision' => $inscripcion->fecha_inscripcion->format('Y-m-d'),
            'fecha_vencimiento' => $inscripcion->fecha_inicio->format('Y-m-d'),
            'moneda' => $inscripcion->moneda,
            'subtotal' => $importe,
            'descuento' => '0.00',
            'recargo' => '0.00',
            'impuestos' => '0.00',
            'total' => $importe,
            'saldo_pendiente' => $importe,
            'estado' => Cargo::ESTADO_PENDIENTE,
            'origen' => Cargo::ORIGEN_AUTOMATICO,
            'observaciones' => 'Cargo generado desde la inscripción '.$inscripcion->getKey().'.',
        ], $usuarioId);
    }

    private function crearMensualidades(Inscripcion $inscripcion, ?int $usuarioId): Collection
    {
        if (! $this->esPositivo($inscripcion->monto_mensualidad)) {
            return collect();
        }

        $concepto = $this->conceptoActivo('MENSUALIDAD');
        $inicio = CarbonImmutable::parse($inscripcion->fecha_inicio)->startOfMonth();
        $fin = $inscripcion->fecha_fin ? CarbonImmutable::parse($inscripcion->fecha_fin)->startOfMonth() : null;
        $importe = $inscripcion->monto_mensualidad;
        $cargos = collect();

        for ($indice = 0; $indice < $inscripcion->numero_mensualidades; $indice++) {
            $periodo = $inicio->addMonthsNoOverflow($indice);
            if ($fin !== null && $periodo->greaterThan($fin)) {
                break;
            }

            $vencimiento = $periodo->day(min($inscripcion->dia_vencimiento, $periodo->daysInMonth));
            $periodoTexto = $periodo->format('Y-m');
            $cargos->push($this->primeroOCrear('MENSUALIDAD:'.$inscripcion->getKey().':'.$periodoTexto, [
                'inscripciones_id' => $inscripcion->getKey(),
                'concepto_cobro_id' => $concepto->getKey(),
                'periodo_anio' => (int) $periodo->format('Y'),
                'periodo_mes' => (int) $periodo->format('n'),
                'fecha_emision' => $periodo->format('Y-m-d'),
                'fecha_vencimiento' => $vencimiento->format('Y-m-d'),
                'moneda' => $inscripcion->moneda,
                'subtotal' => $importe,
                'descuento' => '0.00',
                'recargo' => '0.00',
                'impuestos' => '0.00',
                'total' => $importe,
                'saldo_pendiente' => $importe,
                'estado' => Cargo::ESTADO_PENDIENTE,
                'origen' => Cargo::ORIGEN_AUTOMATICO,
                'observaciones' => 'Mensualidad del periodo '.$periodoTexto.'.',
            ], $usuarioId));
        }

        return $cargos;
    }

    private function conceptoActivo(string $clave): ConceptoCobro
    {
        $concepto = ConceptoCobro::query()->where('clave', $clave)->activos()->first();
        if ($concepto === null) {
            throw new InscripcionFinancieraInvalidaException("El concepto de cobro {$clave} no existe o está inactivo.");
        }

        return $concepto;
    }

    private function primeroOCrear(string $clave, array $atributos, ?int $usuarioId): Cargo
    {
        $existente = Cargo::query()->where('clave_idempotencia', $clave)->first();
        if ($existente !== null) {
            return $existente;
        }

        $cargo = new Cargo($atributos + ['clave_idempotencia' => $clave]);
        $cargo->created_by = $usuarioId;

        try {
            $cargo->save();
            return $cargo;
        } catch (QueryException $exception) {
            if (! $this->esConflictoDeClaveIdempotencia($exception)) {
                throw $exception;
            }

            return Cargo::query()->where('clave_idempotencia', $clave)->firstOrFail();
        }
    }

    private function esConflictoDeClaveIdempotencia(QueryException $exception): bool
    {
        $estado = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $mensaje = strtolower((string) ($exception->errorInfo[2] ?? $exception->getMessage()));

        return in_array($estado, ['23000', '23505'], true)
            && (str_contains($mensaje, 'clave_idempotencia') || str_contains($mensaje, 'cargos_clave_idempotencia_unique'));
    }

    private function esPositivo($importe): bool
    {
        if ($importe === null) {
            return false;
        }

        return preg_match('/^\+?(?:0*[1-9]\d*)(?:\.\d+)?$/', trim((string) $importe)) === 1
            || preg_match('/^\+?0*\.0*[1-9]\d*$/', trim((string) $importe)) === 1;
    }
}
