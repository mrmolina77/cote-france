<?php

namespace App\Http\Livewire;

use App\Models\PerfilFiscal;
use App\Models\Prospecto;
use App\Services\Facturacion\PerfilFiscalService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ShowPerfilesFiscales extends Component
{
    use WithPagination;

    public $busqueda = '';
    public $alumnoId;
    public $editingId;
    public $open_form = false;
    public $tipo_persona = PerfilFiscal::FISICA, $rfc = '', $nombre_razon_social = '', $codigo_postal_fiscal = '';
    public $regimen_fiscal = '', $uso_cfdi = '', $correo_facturacion = '', $relacion_alumno = '';
    public $curp = '', $nivel_educativo = '', $rvoe = '', $predeterminado = false, $activo = true, $fecha_validacion;

    public function mount(): void { Gate::authorize('manage-fiscal-profiles'); }
    public function updatedBusqueda(): void { Gate::authorize('manage-fiscal-profiles'); $this->resetPage(); }
    public function seleccionarAlumno($id): void
    {
        Gate::authorize('manage-fiscal-profiles');
        $alumno = Prospecto::find($id); abort_unless($alumno, 404);
        $this->alumnoId = $alumno->getKey(); $this->busqueda = ''; $this->cancel(); $this->resetPage();
    }
    public function create(): void { Gate::authorize('manage-fiscal-profiles'); abort_unless(Prospecto::find($this->alumnoId), 404); $this->resetForm(); $this->open_form = true; }
    public function edit($id): void
    {
        Gate::authorize('manage-fiscal-profiles'); $perfil = $this->perfilDelAlumno($id);
        $this->editingId = $perfil->getKey();
        foreach ($this->campos() as $campo) $this->{$campo} = $perfil->{$campo} instanceof \DateTimeInterface ? $perfil->{$campo}->format('Y-m-d') : $perfil->{$campo};
        $this->open_form = true;
    }
    public function save(): void
    {
        Gate::authorize('manage-fiscal-profiles'); abort_unless(Prospecto::find($this->alumnoId), 404);
        $perfil = $this->editingId ? $this->perfilDelAlumno($this->editingId) : null;
        app(PerfilFiscalService::class)->guardar(['prospectos_id' => $this->alumnoId] + collect($this->campos())->mapWithKeys(fn ($f) => [$f => $this->{$f}])->all(), auth()->id(), $perfil);
        $this->cancel(); $this->emit('alert', 'El perfil fiscal fue guardado satisfactoriamente.');
    }
    public function activar($id): void { $this->estado($id, true); }
    public function desactivar($id): void { $this->estado($id, false); }
    public function predeterminar($id): void
    {
        Gate::authorize('manage-fiscal-profiles'); $perfil = $this->perfilDelAlumno($id); abort_unless($perfil->activo, 422);
        app(PerfilFiscalService::class)->guardar($perfil->only(array_merge(['prospectos_id'], $this->campos())) + ['predeterminado' => true], auth()->id(), $perfil);
        $this->emit('alert', 'El perfil fiscal predeterminado fue actualizado.');
    }
    public function cancel(): void { Gate::authorize('manage-fiscal-profiles'); $this->resetForm(); $this->open_form = false; }

    public function render()
    {
        Gate::authorize('manage-fiscal-profiles'); $term = trim((string) $this->busqueda);
        $alumnos = collect();
        if ($term !== '') $alumnos = Prospecto::query()->where(fn ($q) => $q->where('prospectos_nombres','like','%'.$term.'%')->orWhere('prospectos_apellidos','like','%'.$term.'%'))->limit(20)->get();
        $alumno = $this->alumnoId ? Prospecto::find($this->alumnoId) : null;
        if ($this->alumnoId && ! $alumno) $this->alumnoId = null;
        $perfiles = PerfilFiscal::query()->where('prospectos_id', $alumno?->getKey() ?? 0)->orderByDesc('predeterminado')->orderByDesc('activo')->paginate(15);
        return view('livewire.show-perfiles-fiscales', compact('alumnos','alumno','perfiles'));
    }

    private function estado($id, bool $activo): void { Gate::authorize('manage-fiscal-profiles'); app(PerfilFiscalService::class)->cambiarEstado($this->perfilDelAlumno($id), $activo, auth()->id()); $this->emit('alert', $activo ? 'Perfil activado.' : 'Perfil desactivado.'); }
    private function perfilDelAlumno($id): PerfilFiscal { $p = PerfilFiscal::find($id); abort_unless($p && (int) $p->prospectos_id === (int) $this->alumnoId, 404); return $p; }
    private function campos(): array { return ['tipo_persona','rfc','nombre_razon_social','codigo_postal_fiscal','regimen_fiscal','uso_cfdi','correo_facturacion','relacion_alumno','curp','nivel_educativo','rvoe','predeterminado','activo','fecha_validacion']; }
    private function resetForm(): void { $this->reset(array_merge(['editingId'], $this->campos())); $this->tipo_persona = PerfilFiscal::FISICA; $this->activo = true; $this->predeterminado = false; $this->resetErrorBag(); }
}
