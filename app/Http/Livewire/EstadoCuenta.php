<?php

namespace App\Http\Livewire;

use App\Models\Inscripcion;
use App\Services\Facturacion\CalculadorEstadoCuenta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class EstadoCuenta extends Component
{
    private const BUSQUEDA_MAXIMA = 120;

    public $busqueda = '';
    public $inscripcionSeleccionadaId;

    public function mount($inscripcion = null): void
    {
        Gate::authorize('manage-cargos');
        if ($inscripcion !== null) {
            $id = $this->normalizarId($inscripcion);
            abort_unless($id !== null && Inscripcion::query()->whereKey($id)->exists(), 404);
            $this->inscripcionSeleccionadaId = $id;
        }
    }

    public function updatedBusqueda(): void
    {
        Gate::authorize('manage-cargos');
        $this->busqueda = mb_substr((string) $this->busqueda, 0, self::BUSQUEDA_MAXIMA);
    }

    public function seleccionarInscripcion($inscripcionId): void
    {
        Gate::authorize('manage-cargos');
        $id = $this->normalizarId($inscripcionId);
        abort_unless($id !== null && Inscripcion::query()->whereKey($id)->exists(), 404);
        $this->inscripcionSeleccionadaId = $id;
    }

    public function limpiarSeleccion(): void
    {
        Gate::authorize('manage-cargos');
        $this->inscripcionSeleccionadaId = null;
    }

    public function limpiarBusqueda(): void
    {
        Gate::authorize('manage-cargos');
        $this->busqueda = '';
    }

    public function render(CalculadorEstadoCuenta $calculador)
    {
        Gate::authorize('manage-cargos');
        $inscripcion = null;
        $resultados = collect();
        $gruposCargos = collect();
        $pagos = collect();
        $resumen = $calculador->calcular(collect());

        $id = $this->normalizarId($this->inscripcionSeleccionadaId);
        if ($this->inscripcionSeleccionadaId !== null) {
            abort_unless($id !== null, 404);
            $inscripcion = Inscripcion::query()->with(['prospecto', 'cursos', 'grupo', 'responsablePago'])->find($id);
            abort_unless($inscripcion, 404);

            $cargos = $inscripcion->cargos()->with('conceptoCobro')
                ->orderByRaw('CASE WHEN periodo_anio IS NULL OR periodo_mes IS NULL THEN 1 ELSE 0 END')
                ->orderBy('periodo_anio')->orderBy('periodo_mes')
                ->orderBy('fecha_vencimiento')->orderBy('cargo_id')->get();
            foreach ($cargos as $cargo) {
                foreach ($calculador->valoresPresentacion($cargo) as $atributo => $valor) {
                    $cargo->setAttribute($atributo, $valor);
                }
                $cargo->periodo_presentacion = $cargo->periodo_anio && $cargo->periodo_mes
                    ? sprintf('%04d-%02d', $cargo->periodo_anio, $cargo->periodo_mes)
                    : 'Sin periodo';
            }
            $gruposCargos = $cargos->groupBy('periodo_presentacion')->sortKeysUsing(function ($a, $b) {
                if ($a === $b) return 0;
                if ($a === 'Sin periodo') return 1;
                if ($b === 'Sin periodo') return -1;
                return strcmp($a, $b);
            });
            $resumen = $calculador->calcular($cargos);
            $pagos = $inscripcion->pagos()->with(['metodoPago', 'confirmedBy', 'aplicaciones.cargo.conceptoCobro'])
                ->orderByDesc('fecha_pago')->orderByDesc('pago_id')->get();
        } else {
            $termino = mb_substr(trim(is_string($this->busqueda) ? $this->busqueda : ''), 0, self::BUSQUEDA_MAXIMA);
            if ($termino !== '') {
                $resultados = $this->consultaBusqueda($termino)->limit(25)->get();
            }
        }

        return view('livewire.estado-cuenta', compact('inscripcion', 'resultados', 'gruposCargos', 'pagos', 'resumen'));
    }

    private function consultaBusqueda(string $termino): Builder
    {
        $query = Inscripcion::query()->with(['prospecto', 'cursos', 'grupo']);
        if (ctype_digit($termino) && (int) $termino > 0) {
            return $query->whereKey((int) $termino);
        }
        $patron = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $termino).'%';
        return $query->whereHas('prospecto', function (Builder $query) use ($patron) {
            $query->whereRaw("prospectos_nombres LIKE ? ESCAPE '!'", [$patron])
                ->orWhereRaw("prospectos_apellidos LIKE ? ESCAPE '!'", [$patron]);
        })->orderBy('inscripciones_id');
    }

    private function normalizarId($id): ?int
    {
        return (is_int($id) || is_string($id)) && ctype_digit((string) $id) && (int) $id > 0 ? (int) $id : null;
    }
}
