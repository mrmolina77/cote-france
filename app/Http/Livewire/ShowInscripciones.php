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
use Livewire\WithPagination;

class ShowInscripciones extends Component
{
    use WithPagination, ManagesEnrollmentFinancialData;

    public $search = '';
    public $sort = 'inscripciones_id';
    public $direction = 'asc';
    public $inscripcion;
    public $cant = 50;
    public $readyToLoad = false;
    public $prospectos = [];
    public $open_edit = false;
    protected $listeners = ['render', 'delete'];

    private const SORT_COLUMNS = [
        'inscripciones_id' => 'inscripciones.inscripciones_id',
        'fecha_inscripcion' => 'inscripciones.fecha_inscripcion',
        'cursos_id' => 'cursos.cursos_descripcion',
        'grupo_id' => 'grupos.grupo_nombre',
        'prospectos_id' => 'prospectos.prospectos_nombres',
        'estatus' => 'inscripciones.estatus',
        'monto_mensualidad' => 'inscripciones.monto_mensualidad',
    ];

    public function mount() { Gate::authorize('manage-inscripciones'); }
    public function updatingSearch() { $this->resetPage(); }
    public function loadPosts() { $this->readyToLoad = true; }

    protected function rules(): array
    {
        return array_merge([
            'inscripcion.prospectos_id' => 'required|integer|exists:prospectos,prospectos_id',
            'inscripcion.cursos_id' => 'required|integer|exists:cursos,cursos_id',
            'inscripcion.grupo_id' => 'required|integer|exists:grupos,grupo_id',
            'inscripcion.fecha_inscripcion' => 'required|date',
            'inscripcion.created_by' => 'nullable',
        ], $this->financialRules('inscripcion.'));
    }

    public function render()
    {
        $inscripciones = [];
        if ($this->readyToLoad) {
            $term = trim($this->search);
            $inscripciones = DB::table('inscripciones')
                ->join('prospectos', 'prospectos.prospectos_id', '=', 'inscripciones.prospectos_id')
                ->join('cursos', 'cursos.cursos_id', '=', 'inscripciones.cursos_id')
                ->join('grupos', 'grupos.grupo_id', '=', 'inscripciones.grupo_id')
                ->leftJoin('responsables_pago', 'responsables_pago.responsable_pago_id', '=', 'inscripciones.responsable_pago_id')
                ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term) {
                    $like = '%'.$term.'%';
                    $query->where('prospectos.prospectos_nombres', 'like', $like)
                        ->orWhere('prospectos.prospectos_apellidos', 'like', $like)
                        ->orWhere('cursos.cursos_descripcion', 'like', $like)
                        ->orWhere('inscripciones.fecha_inscripcion', 'like', $like)
                        ->orWhere('inscripciones.estatus', 'like', $like)
                        ->orWhere('responsables_pago.nombre_razon_social', 'like', $like);
                }))
                ->select('inscripciones.*', 'prospectos.prospectos_nombres', 'prospectos.prospectos_apellidos',
                    'cursos.cursos_descripcion', 'grupos.grupo_nombre', 'responsables_pago.nombre_razon_social as responsable_nombre')
                ->orderBy($this->sortColumn(), $this->safeDirection())->paginate($this->cant);
        }

        return view('livewire.show-inscripciones', ['inscripciones' => $inscripciones, 'grupos' => Grupo::all(),
            'cursos' => Curso::all(), 'responsables' => ResponsablePago::where('activo', true)->orderBy('nombre_razon_social')->get()]);
    }

    public function order($order)
    {
        if (! array_key_exists($order, self::SORT_COLUMNS)) { $this->sort = 'inscripciones_id'; $this->direction = 'asc'; return; }
        if ($this->sort === $order) $this->direction = $this->direction === 'desc' ? 'asc' : 'desc';
        else { $this->sort = $order; $this->direction = 'asc'; }
    }

    public function edit($id)
    {
        Gate::authorize('manage-inscripciones');
        $this->inscripcion = Inscripcion::findOrFail($id);
        foreach (['fecha_inscripcion','fecha_inicio','fecha_fin'] as $field) {
            if ($this->inscripcion->{$field}) $this->inscripcion->{$field} = $this->inscripcion->{$field}->format('Y-m-d');
        }
        $this->prospectos = Prospecto::whereNotIn('prospectos_id', fn ($q) => $q->select('prospectos_id')->from('inscripciones'))
            ->orWhere('prospectos_id', $this->inscripcion->prospectos_id)->get();
        $this->responsable_opcion = 'conservar';
        $this->responsable_pago_id = $this->inscripcion->responsable_pago_id;
        $this->resetErrorBag();
        $this->open_edit = true;
    }

    public function update()
    {
        Gate::authorize('manage-inscripciones');
        $validated = $this->validate();
        $this->validateFinancialCombination('inscripcion.');
        if (Inscripcion::where('prospectos_id', $this->inscripcion->prospectos_id)->where('inscripciones_id', '<>', $this->inscripcion->getKey())->exists()) {
            $this->addError('inscripcion.prospectos_id', 'El prospecto ya cuenta con una inscripción.'); return;
        }
        DB::transaction(function () use ($validated) {
            $inscripcion = Inscripcion::findOrFail($this->inscripcion->getKey());
            $fields = ['prospectos_id','cursos_id','grupo_id','fecha_inscripcion','estatus','fecha_inicio','fecha_fin',
                'moneda','monto_inscripcion','monto_mensualidad','dia_vencimiento','numero_mensualidades','descuento',
                'beca','observaciones_financieras'];
            foreach ($fields as $field) {
                $value = data_get($validated, 'inscripcion.'.$field);
                $inscripcion->{$field} = $field === 'moneda' ? 'MXN' : $this->blankToNull($value);
            }
            if ($this->responsable_opcion !== 'conservar') {
                $inscripcion->responsable_pago_id = $this->resolveResponsable((int) $inscripcion->prospectos_id)->getKey();
            }
            $inscripcion->save();
        });
        $this->reset(['open_edit', 'responsable_pago_id', 'responsable_nombre', 'responsable_telefono', 'responsable_correo']);
        $this->emit('alert', 'La inscripción fue modificada satisfactoriamente.');
    }

    public function delete(Inscripcion $inscripcion) { Gate::authorize('manage-inscripciones'); $inscripcion->delete(); $this->emit('alert', 'La inscripción fue eliminada satisfactoriamente.'); }
    private function sortColumn() { return self::SORT_COLUMNS[$this->sort] ?? self::SORT_COLUMNS['inscripciones_id']; }
    private function safeDirection() { return in_array($this->direction, ['asc','desc'], true) ? $this->direction : 'asc'; }
}
