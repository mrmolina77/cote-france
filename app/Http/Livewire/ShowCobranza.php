<?php

namespace App\Http\Livewire;

use App\Models\Cargo;
use App\Models\Inscripcion;
use App\Models\MetodoPago;
use App\Models\Pago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ShowCobranza extends Component
{
    use WithPagination;

    private const POR_PAGINA = [10, 25, 50];
    private const BUSQUEDA_MAXIMA = 120;

    public $busqueda = '';
    public $estado = 'todos';
    public $metodoPagoId = 'todos';
    public $fechaDesde = '';
    public $fechaHasta = '';
    public $porPagina = 10;

    public function mount(): void
    {
        Gate::authorize('view-financial');
    }

    public function updated($name): void
    {
        Gate::authorize('view-financial');

        if (in_array($name, ['busqueda', 'estado', 'metodoPagoId', 'fechaDesde', 'fechaHasta', 'porPagina'], true)) {
            $this->resetPage();
        }
        if (in_array($name, ['fechaDesde', 'fechaHasta'], true)) {
            $this->validarFechas();
        }
    }

    public function limpiarFiltros(): void
    {
        Gate::authorize('view-financial');
        $this->reset(['busqueda', 'fechaDesde', 'fechaHasta']);
        $this->estado = 'todos';
        $this->metodoPagoId = 'todos';
        $this->porPagina = 10;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('view-financial');

        $busqueda = mb_substr(trim(is_string($this->busqueda) ? $this->busqueda : ''), 0, self::BUSQUEDA_MAXIMA);
        $estado = is_string($this->estado) && in_array($this->estado, Pago::ESTADOS, true) ? $this->estado : 'todos';
        $metodoId = $this->normalizarId($this->metodoPagoId);
        $porPaginaSolicitado = (is_int($this->porPagina) || is_string($this->porPagina)) && ctype_digit((string) $this->porPagina)
            ? (int) $this->porPagina
            : 0;
        $porPagina = in_array($porPaginaSolicitado, self::POR_PAGINA, true) ? $porPaginaSolicitado : 10;
        $this->porPagina = $porPagina;

        $movimientos = Pago::query()->with(['prospecto', 'inscripcion', 'metodoPago']);
        if ($busqueda !== '') {
            $patron = '%'.$this->escaparLike($busqueda).'%';
            $movimientos->where(function (Builder $query) use ($busqueda, $patron) {
                $query->whereRaw("folio LIKE ? ESCAPE '!'", [$patron])
                    ->orWhereRaw("referencia LIKE ? ESCAPE '!'", [$patron])
                    ->orWhereRaw("rastreo_spei LIKE ? ESCAPE '!'", [$patron])
                    ->orWhereHas('prospecto', function (Builder $prospectos) use ($patron) {
                        $prospectos->whereRaw("prospectos_nombres LIKE ? ESCAPE '!'", [$patron])
                            ->orWhereRaw("prospectos_apellidos LIKE ? ESCAPE '!'", [$patron]);
                    });
                if (ctype_digit($busqueda) && (int) $busqueda > 0) {
                    $query->orWhere('inscripciones_id', (int) $busqueda);
                }
            });
        }
        if ($estado !== 'todos') {
            $movimientos->where('estado', $estado);
        }
        if ($metodoId !== null) {
            $movimientos->where('metodo_pago_id', $metodoId);
        }
        if ($this->fechasValidas()) {
            if (is_string($this->fechaDesde) && $this->fechaDesde !== '') {
                $movimientos->whereDate('fecha_pago', '>=', $this->fechaDesde);
            }
            if (is_string($this->fechaHasta) && $this->fechaHasta !== '') {
                $movimientos->whereDate('fecha_pago', '<=', $this->fechaHasta);
            }
        }

        $ahora = now(config('app.timezone'));
        $inicioMes = $ahora->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $finMes = $ahora->copy()->endOfMonth()->format('Y-m-d H:i:s');

        return view('livewire.show-cobranza', [
            // Los filtros pertenecen sólo a la tabla: los KPI siempre conservan su definición global.
            'kpis' => [
                'cobrado' => (string) Pago::query()->confirmados()->whereBetween('fecha_pago', [$inicioMes, $finMes])->sum('monto'),
                'pendiente' => (string) Cargo::query()->abiertos()->where('saldo_pendiente', '>', 0)->sum('saldo_pendiente'),
                'vencido' => (string) Cargo::query()->vencidos()->where('saldo_pendiente', '>', 0)->sum('saldo_pendiente'),
                'estudiantes' => Inscripcion::query()->where('estatus', 'activa')->distinct()->count('prospectos_id'),
                'porConfirmar' => Pago::query()->borradores()->count(),
                'cancelados' => Pago::query()->cancelados()->count(),
            ],
            'movimientos' => $movimientos->orderByDesc('fecha_pago')->orderByDesc('pago_id')->paginate($porPagina),
            'metodos' => MetodoPago::query()->ordenados()->get(['metodo_pago_id', 'nombre']),
        ]);
    }

    private function escaparLike(string $valor): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $valor);
    }

    private function normalizarId($id): ?int
    {
        if ((! is_int($id) && ! is_string($id)) || ! ctype_digit((string) $id) || (int) $id < 1) {
            return null;
        }

        return (int) $id;
    }

    private function fechasValidas(bool $mostrarErrores = false): bool
    {
        $validas = true;
        foreach (['fechaDesde' => 'La fecha desde', 'fechaHasta' => 'La fecha hasta'] as $campo => $etiqueta) {
            $valor = $this->{$campo};
            $fecha = is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $valor) === 1
                ? \DateTimeImmutable::createFromFormat('!Y-m-d', $valor)
                : false;
            $esValida = $valor === '' || ($fecha !== false && $fecha->format('Y-m-d') === $valor);
            if (! $esValida) {
                $validas = false;
                if ($mostrarErrores) {
                    $this->addError($campo, $etiqueta.' debe ser una fecha real con formato AAAA-MM-DD.');
                }
            }
        }
        if ($validas && $this->fechaDesde !== '' && $this->fechaHasta !== '' && $this->fechaDesde > $this->fechaHasta) {
            $validas = false;
            if ($mostrarErrores) {
                $this->addError('fechaHasta', 'La fecha hasta debe ser posterior o igual a la fecha desde.');
            }
        }

        return $validas;
    }

    private function validarFechas(): void
    {
        $this->resetErrorBag(['fechaDesde', 'fechaHasta']);
        $this->fechasValidas(true);
    }
}
