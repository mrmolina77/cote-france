<?php

namespace App\Http\Livewire;

use App\Models\AuditoriaPago;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ShowAuditoriaPagos extends Component
{
    use WithPagination;

    public $busqueda = '';
    public $accion = '';
    public $usuarioId = '';
    public $fechaDesde = '';
    public $fechaHasta = '';
    public $porPagina = 25;
    public $detalleId;

    public function mount(): void { Gate::authorize('audit-payments'); }
    public function updated(): void { Gate::authorize('audit-payments'); $this->resetPage(); }
    public function verDetalle($id): void
    {
        Gate::authorize('audit-payments');
        abort_unless((is_int($id) || (is_string($id) && ctype_digit($id)))
            && (int) $id > 0
            && AuditoriaPago::query()->whereKey((int) $id)->exists(), 404);
        $this->detalleId = (int) $id;
    }
    public function cerrarDetalle(): void { Gate::authorize('audit-payments'); $this->detalleId = null; }

    public function render()
    {
        Gate::authorize('audit-payments');
        $query = AuditoriaPago::query()->with(['pago.prospecto', 'usuario'])->whereHas('pago');
        $buscar = mb_substr(trim((string) $this->busqueda), 0, 120);
        if ($buscar !== '') $query->whereHas('pago', function (Builder $q) use ($buscar) {
            $q->where('folio', 'like', '%'.$buscar.'%')->orWhere('inscripciones_id', ctype_digit($buscar) ? (int) $buscar : -1)
                ->orWhereHas('prospecto', fn (Builder $p) => $p->where('prospectos_nombres', 'like', '%'.$buscar.'%')
                    ->orWhere('prospectos_apellidos', 'like', '%'.$buscar.'%'));
        });
        if (in_array($this->accion, AuditoriaPago::ACCIONES, true)) $query->where('accion', $this->accion);
        if (ctype_digit((string) $this->usuarioId)) $query->where('usuario_id', (int) $this->usuarioId);
        $desde = $this->fecha($this->fechaDesde, false); $hasta = $this->fecha($this->fechaHasta, true);
        $fechasInvalidas = (($this->fechaDesde !== '' && ! $desde) || ($this->fechaHasta !== '' && ! $hasta) || ($desde && $hasta && $desde->gt($hasta)));
        if ($fechasInvalidas) $query->whereRaw('1 = 0');
        else { if ($desde) $query->where('ocurrido_en', '>=', $desde); if ($hasta) $query->where('ocurrido_en', '<=', $hasta); }
        $porPagina = in_array((int) $this->porPagina, [10,25,50], true) ? (int) $this->porPagina : 25;
        $detalle = $this->detalleId ? AuditoriaPago::with(['pago.prospecto','usuario'])->whereHas('pago')->find((int) $this->detalleId) : null;
        return view('livewire.show-auditoria-pagos', ['auditorias'=>$query->orderByDesc('ocurrido_en')->orderByDesc('auditoria_pago_id')->paginate($porPagina),
            'acciones'=>AuditoriaPago::ACCIONES, 'usuarios'=>User::query()->orderBy('name')->get(['id','name']), 'detalle'=>$detalle,
            'fechasInvalidas'=>$fechasInvalidas]);
    }

    private function fecha($valor, bool $fin): ?CarbonImmutable
    {
        if ($valor === '') return null;
        if (! is_string($valor) || preg_match('/^\d{4}-\d{2}-\d{2}$/D', $valor) !== 1) return null;
        try { $fecha = CarbonImmutable::createFromFormat('!Y-m-d', $valor); } catch (\Throwable $e) { return null; }
        return $fecha->format('Y-m-d') === $valor ? ($fin ? $fecha->endOfDay() : $fecha->startOfDay()) : null;
    }
}
