<?php

namespace App\Http\Livewire;

use App\Models\MetodoPago;
use App\Models\Pago;
use App\Services\Facturacion\CancelarPagoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class ShowPagos extends Component
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
    public $pagoDetalleId;
    public $pagoCancelarId;
    public $motivoCancelacion = '';
    public $mostrarModalDetalle = false;
    public $mostrarModalCancelacion = false;

    public function mount(): void
    {
        Gate::authorize('manage-pagos');
    }

    public function updated($name): void
    {
        Gate::authorize('manage-pagos');
        if (in_array($name, ['busqueda', 'estado', 'metodoPagoId', 'fechaDesde', 'fechaHasta', 'porPagina'], true)) {
            $this->resetPage();
        }
    }

    public function verDetalle($pagoId): void
    {
        Gate::authorize('manage-pagos');
        $id = $this->normalizarId($pagoId);
        abort_unless($id !== null && Pago::query()->whereKey($id)->exists(), 404);
        $this->pagoDetalleId = $id;
        $this->mostrarModalDetalle = true;
    }

    public function cerrarDetalle(): void
    {
        Gate::authorize('manage-pagos');
        $this->pagoDetalleId = null;
        $this->mostrarModalDetalle = false;
    }

    public function prepararCancelacion($pagoId): void
    {
        Gate::authorize('cancel-pagos');
        $id = $this->normalizarId($pagoId);
        $pago = $id === null ? null : Pago::query()->whereKey($id)->first();
        if (! $pago || $pago->estado !== Pago::ESTADO_CONFIRMADO) {
            throw ValidationException::withMessages(['pagoCancelarId' => 'El pago seleccionado no está disponible para cancelación.']);
        }

        $this->resetErrorBag();
        $this->resetValidation();
        $this->pagoCancelarId = $pago->getKey();
        $this->motivoCancelacion = '';
        $this->mostrarModalCancelacion = true;
    }

    public function cerrarCancelacion(): void
    {
        Gate::authorize('cancel-pagos');
        $this->limpiarCancelacion();
    }

    public function confirmarCancelacion(CancelarPagoService $service): void
    {
        Gate::authorize('cancel-pagos');
        $id = $this->normalizarId($this->pagoCancelarId);
        $motivo = is_string($this->motivoCancelacion) ? trim($this->motivoCancelacion) : '';
        $this->motivoCancelacion = $motivo;
        $this->validate([
            'pagoCancelarId' => ['required'],
            'motivoCancelacion' => ['required', 'string', 'max:2000'],
        ], [], [
            'pagoCancelarId' => 'pago',
            'motivoCancelacion' => 'motivo de cancelación',
        ]);
        if ($id === null) {
            $this->addError('pagoCancelarId', 'El pago seleccionado no es válido.');
            return;
        }

        try {
            $pago = $service->cancelar($id, $motivo, (int) Auth::id());
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                $this->addError($key === 'motivo' ? 'motivoCancelacion' : 'pagoCancelarId', $messages[0]);
            }
            return;
        }

        $folio = $pago->folio;
        $this->limpiarCancelacion();
        session()->flash('status', 'El pago '.$folio.' fue cancelado correctamente.');
    }

    public function render()
    {
        Gate::authorize('manage-pagos');
        $busqueda = mb_substr(trim(is_string($this->busqueda) ? $this->busqueda : ''), 0, self::BUSQUEDA_MAXIMA);
        $estado = in_array($this->estado, Pago::ESTADOS, true) ? $this->estado : 'todos';
        $porPagina = in_array((int) $this->porPagina, self::POR_PAGINA, true) ? (int) $this->porPagina : 10;
        $this->porPagina = $porPagina;
        $metodoId = $this->normalizarId($this->metodoPagoId);

        $query = Pago::query()->with(['prospecto', 'responsablePago', 'metodoPago', 'confirmedBy']);
        if ($busqueda !== '') {
            $query->where(function (Builder $query) use ($busqueda) {
                $query->where('folio', 'like', '%'.$busqueda.'%')
                    ->orWhere('referencia', 'like', '%'.$busqueda.'%')
                    ->orWhere('rastreo_spei', 'like', '%'.$busqueda.'%')
                    ->orWhereHas('prospecto', function (Builder $prospectos) use ($busqueda) {
                        $prospectos->where('prospectos_nombres', 'like', '%'.$busqueda.'%')
                            ->orWhere('prospectos_apellidos', 'like', '%'.$busqueda.'%');
                    });
                if (ctype_digit($busqueda)) {
                    $query->orWhere('inscripciones_id', (int) $busqueda);
                }
            });
        }
        if ($estado !== 'todos') $query->where('estado', $estado);
        if ($metodoId !== null) $query->where('metodo_pago_id', $metodoId);
        if ($this->fechaValida($this->fechaDesde)) $query->whereDate('fecha_pago', '>=', $this->fechaDesde);
        if ($this->fechaValida($this->fechaHasta)) $query->whereDate('fecha_pago', '<=', $this->fechaHasta);

        $detalle = $this->pagoDetalleId ? Pago::query()->with([
            'prospecto', 'responsablePago', 'metodoPago', 'confirmedBy', 'cancelledBy',
            'aplicaciones.cargo.conceptoCobro',
        ])->find($this->normalizarId($this->pagoDetalleId)) : null;
        $pagoCancelar = $this->pagoCancelarId ? Pago::query()->with(['prospecto', 'metodoPago'])->find($this->normalizarId($this->pagoCancelarId)) : null;

        return view('livewire.show-pagos', [
            'pagos' => $query->orderByDesc('fecha_pago')->orderByDesc('pago_id')->paginate($porPagina),
            'metodos' => MetodoPago::query()->ordenados()->get(['metodo_pago_id', 'nombre']),
            'detalle' => $detalle,
            'pagoCancelar' => $pagoCancelar,
        ]);
    }

    private function limpiarCancelacion(): void
    {
        $this->pagoCancelarId = null;
        $this->motivoCancelacion = '';
        $this->mostrarModalCancelacion = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function normalizarId($id): ?int
    {
        if ((! is_int($id) && ! is_string($id)) || ! ctype_digit((string) $id) || (int) $id < 1) return null;
        return (int) $id;
    }

    private function fechaValida($fecha): bool
    {
        return is_string($fecha) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $fecha) === 1;
    }
}
