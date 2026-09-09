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
    public $saldoPendiente = '0.00';
    public $saldoVencido = '0.00';
    public $cantidadCargosAbiertos = 0;
    public $cantidadCargosVencidos = 0;
    public $proximoVencimiento;

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

        abort_unless(is_int($inscripcionId) || (is_string($inscripcionId) && preg_match('/^[1-9][0-9]*$/D', $inscripcionId)), 404);

        $inscripcion = Inscripcion::query()
            ->with(['prospecto', 'cursos', 'grupo', 'responsablePago'])
            ->find((int) $inscripcionId);

        abort_unless($inscripcion && $inscripcion->prospecto, 404);

        $this->inscripcionSeleccionadaId = $inscripcion->getKey();
        $this->cargarResumenFinanciero();
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
        if ($this->inscripcionSeleccionadaId) {
            $inscripcion = Inscripcion::query()
                ->with(['prospecto', 'cursos', 'grupo', 'responsablePago'])
                ->find($this->inscripcionSeleccionadaId);

            if (! $inscripcion || ! $inscripcion->prospecto) {
                $this->limpiarDatosSeleccionados();
            } else {
                $cargos = $this->consultaCargosAbiertos()
                    ->with('conceptoCobro')
                    ->orderByRaw('CASE WHEN estado = ? OR fecha_vencimiento < ? THEN 0 ELSE 1 END', [Cargo::ESTADO_VENCIDO, now()->toDateString()])
                    ->orderBy('fecha_vencimiento')
                    ->orderBy('cargo_id')
                    ->get();
            }
        }

        return view('livewire.registrar-pago', compact('resultados', 'inscripcion', 'cargos'));
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

    private function consultaCargosAbiertos(): Builder
    {
        return Cargo::query()
            ->where('inscripciones_id', $this->inscripcionSeleccionadaId)
            ->whereIn('estado', [Cargo::ESTADO_PENDIENTE, Cargo::ESTADO_PARCIAL, Cargo::ESTADO_VENCIDO])
            ->where('saldo_pendiente', '>', '0.00');
    }

    private function cargarResumenFinanciero(): void
    {
        $hoy = now()->toDateString();
        $cargos = $this->consultaCargosAbiertos()->get(['saldo_pendiente', 'estado', 'fecha_vencimiento']);
        $vencidos = $cargos->filter(fn (Cargo $cargo) => $cargo->estado === Cargo::ESTADO_VENCIDO || $cargo->fecha_vencimiento->toDateString() < $hoy);

        $this->saldoPendiente = $this->sumarImportes($cargos->pluck('saldo_pendiente')->all());
        $this->saldoVencido = $this->sumarImportes($vencidos->pluck('saldo_pendiente')->all());
        $this->cantidadCargosAbiertos = $cargos->count();
        $this->cantidadCargosVencidos = $vencidos->count();
        $proximo = $cargos->filter(fn (Cargo $cargo) => $cargo->estado !== Cargo::ESTADO_VENCIDO && $cargo->fecha_vencimiento->toDateString() >= $hoy)
            ->sortBy('fecha_vencimiento')->first();
        $this->proximoVencimiento = $proximo ? $proximo->fecha_vencimiento->toDateString() : null;
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
        $this->saldoPendiente = '0.00';
        $this->saldoVencido = '0.00';
        $this->cantidadCargosAbiertos = 0;
        $this->cantidadCargosVencidos = 0;
        $this->proximoVencimiento = null;
    }
}
