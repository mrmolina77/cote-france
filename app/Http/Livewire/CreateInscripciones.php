<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\ManagesEnrollmentFinancialData;
use App\Models\Curso;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Prospecto;
use App\Models\ResponsablePago;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CreateInscripciones extends Component
{
    use ManagesEnrollmentFinancialData;

    public $open = false;
    public $fecha_inscripcion;
    public $prospectos_id;
    public $cursos_id;
    public $grupo_id;
    public $estatus = 'activa';
    public $fecha_inicio;
    public $fecha_fin;
    public $moneda = 'MXN';
    public $monto_inscripcion;
    public $monto_mensualidad;
    public $dia_vencimiento;
    public $numero_mensualidades;
    public $descuento = '0.00';
    public $beca = '0.00';
    public $observaciones_financieras;

    public function mount()
    {
        Gate::authorize('manage-inscripciones');
        $this->defaults();
    }

    protected function rules(): array
    {
        return array_merge($this->financialRules('', false), [
            'prospectos_id' => 'required|integer|exists:prospectos,prospectos_id',
            'cursos_id' => 'required|integer|exists:cursos,cursos_id',
            'grupo_id' => 'required|integer|exists:grupos,grupo_id',
            'fecha_inscripcion' => 'required|date',
            'estatus' => 'required|in:activa,suspendida,finalizada,cancelada',
            'fecha_inicio' => 'required|date',
            'moneda' => 'required|in:MXN',
        ]);
    }

    public function updatedFechaInscripcion($value): void
    {
        if (! $this->fecha_inicio) $this->fecha_inicio = $value;
    }

    public function save(): void
    {
        Gate::authorize('manage-inscripciones');
        $this->validate();
        $this->validateFinancialCombination();
        if (Inscripcion::where('prospectos_id', $this->prospectos_id)->exists()) {
            $this->addError('prospectos_id', 'El prospecto ya cuenta con una inscripción.');
            return;
        }

        DB::transaction(function () {
            $responsable = $this->resolveResponsable((int) $this->prospectos_id);
            Inscripcion::create($this->enrollmentData() + ['responsable_pago_id' => $responsable->getKey()]);
        });

        $this->reset();
        $this->defaults();
        $this->resetErrorBag();
        $this->emitTo('show-inscripciones', 'render');
        $this->emit('alert', 'La inscripción fue agregada satisfactoriamente.');
    }

    private function enrollmentData(): array
    {
        $data = [];
        foreach (['fecha_inscripcion','prospectos_id','cursos_id','grupo_id','estatus','fecha_inicio','fecha_fin','monto_inscripcion','monto_mensualidad','dia_vencimiento','numero_mensualidades','descuento','beca','observaciones_financieras'] as $field) {
            $data[$field] = $this->blankToNull($this->{$field});
        }
        $data['moneda'] = 'MXN';
        return $data;
    }

    private function defaults(): void
    {
        $this->fecha_inscripcion = now()->toDateString();
        $this->fecha_inicio = $this->fecha_inscripcion;
        $this->estatus = 'activa';
        $this->moneda = 'MXN';
        $this->descuento = '0.00';
        $this->beca = '0.00';
        $this->responsable_opcion = 'alumno';
        $this->responsable_tipo = 'persona';
    }

    public function render()
    {
        return view('livewire.create-inscripciones', [
            'prospectos' => Prospecto::whereNotIn('prospectos_id', fn ($q) => $q->select('prospectos_id')->from('inscripciones'))->get(),
            'cursos' => Curso::all(), 'grupos' => Grupo::all(),
            'responsables' => ResponsablePago::where('activo', true)->orderBy('nombre_razon_social')->get(),
        ]);
    }
}
