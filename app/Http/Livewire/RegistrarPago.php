<?php

namespace App\Http\Livewire;

use App\Models\Cargo;
use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class RegistrarPago extends Component
{
    public $busqueda = '';
    public $inscripcionSeleccionadaId;

    public function mount(): void
    {
        Gate::authorize('manage-pagos');
    }

    public function updatedBusqueda(): void
    {
        Gate::authorize('manage-pagos');
    }

    public function seleccionarInscripcion($inscripcionId): void
    {
        Gate::authorize('manage-pagos');
        $this->limpiarDatosSeleccionados();

        $inscripcionId = $this->normalizarInscripcionId($inscripcionId);
        abort_unless($inscripcionId !== null, 404);

        $inscripcion = Inscripcion::query()
            ->with(['prospecto', 'cursos', 'grupo', 'responsablePago'])
            ->find($inscripcionId);

        abort_unless($inscripcion && $inscripcion->prospecto, 404);

        $this->inscripcionSeleccionadaId = $inscripcion->getKey();
    }

    public function limpiarSeleccion(): void
    {
        Gate::authorize('manage-pagos');
        $this->limpiarDatosSeleccionados();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function render()
    {
        Gate::authorize('manage-pagos');

        $termino = trim((string) $this->busqueda);
        $resultados = collect();
        if ($termino !== '') {
            $resultados = $this->consultaBusqueda($termino)->limit(25)->get();
        }

        $inscripcion = null;
        $cargos = collect();
        $resumen = $this->resumenVacio();
        $inscripcionId = $this->normalizarInscripcionId($this->inscripcionSeleccionadaId);
        if ($inscripcionId !== null) {
            $inscripcion = Inscripcion::query()
                ->with(['prospecto', 'cursos', 'grupo', 'responsablePago'])
                ->find($inscripcionId);

            if (! $inscripcion || ! $inscripcion->prospecto) {
                $this->limpiarDatosSeleccionados();
            } else {
                // The cards and table are derived from this single, freshly persisted collection.
                $cargos = $this->consultaCargosAbiertos($inscripcionId)
                    ->with('conceptoCobro')
                    ->orderByRaw('CASE WHEN estado = ? OR fecha_vencimiento < ? THEN 0 ELSE 1 END', [Cargo::ESTADO_VENCIDO, now()->toDateString()])
                    ->orderBy('fecha_vencimiento')
                    ->orderBy('cargo_id')
                    ->get();
                $resumen = $this->calcularResumenFinanciero($cargos);
            }
        } elseif ($this->inscripcionSeleccionadaId !== null) {
            $this->limpiarDatosSeleccionados();
        }

        return view('livewire.registrar-pago', compact('resultados', 'inscripcion', 'cargos', 'resumen'));
    }

    private function consultaBusqueda(string $termino): Builder
    {
        $query = Inscripcion::query()->with(['prospecto', 'cursos', 'grupo']);
        if (ctype_digit($termino)) {
            return $query->whereKey((int) $termino)->orderBy('inscripciones_id');
        }

        $palabras = preg_split('/\s+/u', $termino, -1, PREG_SPLIT_NO_EMPTY);
        return $query->whereHas('prospecto', function (Builder $prospectos) use ($palabras) {
            foreach ($palabras as $palabra) {
                $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $palabra).'%';
                $prospectos->where(function (Builder $parte) use ($like) {
                    $parte->whereRaw("prospectos_nombres LIKE ? ESCAPE '!'", [$like])
                        ->orWhereRaw("prospectos_apellidos LIKE ? ESCAPE '!'", [$like]);
                });
            }
        })->orderBy('inscripciones_id');
    }

    private function consultaCargosAbiertos(int $inscripcionId): Builder
    {
        return Cargo::query()
            ->where('inscripciones_id', $inscripcionId)
            ->whereIn('estado', [Cargo::ESTADO_PENDIENTE, Cargo::ESTADO_PARCIAL, Cargo::ESTADO_VENCIDO])
            ->where('saldo_pendiente', '>', '0.00');
    }

    private function calcularResumenFinanciero($cargos): array
    {
        $hoy = now()->toDateString();
        $vencidos = $cargos->filter(fn (Cargo $cargo) => $cargo->estado === Cargo::ESTADO_VENCIDO || $cargo->fecha_vencimiento->toDateString() < $hoy);
        $proximo = $cargos->filter(fn (Cargo $cargo) => $cargo->estado !== Cargo::ESTADO_VENCIDO && $cargo->fecha_vencimiento->toDateString() >= $hoy)
            ->sortBy('fecha_vencimiento')->first();

        return [
            'saldoPendiente' => $this->sumarImportes($cargos->pluck('saldo_pendiente')->all()),
            'saldoVencido' => $this->sumarImportes($vencidos->pluck('saldo_pendiente')->all()),
            'cantidadCargosAbiertos' => $cargos->count(),
            'cantidadCargosVencidos' => $vencidos->count(),
            'proximoVencimiento' => $proximo?->fecha_vencimiento->toDateString(),
        ];
    }

    private function sumarImportes(array $importes): string
    {
        $centavos = 0;
        foreach ($importes as $importe) {
            [$enteros, $decimales] = array_pad(explode('.', (string) $importe, 2), 2, '');
            $centavos += ((int) $enteros * 100) + (int) str_pad(substr($decimales, 0, 2), 2, '0');
        }

        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }

    private function limpiarDatosSeleccionados(): void
    {
        $this->inscripcionSeleccionadaId = null;
    }

    private function normalizarInscripcionId($value): ?int
    {
        if (! is_int($value) && ! (is_string($value) && preg_match('/^[1-9][0-9]*$/D', $value))) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function resumenVacio(): array
    {
        return [
            'saldoPendiente' => '0.00',
            'saldoVencido' => '0.00',
            'cantidadCargosAbiertos' => 0,
            'cantidadCargosVencidos' => 0,
            'proximoVencimiento' => null,
        ];
    }
}
