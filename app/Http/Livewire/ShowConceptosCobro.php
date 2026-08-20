<?php

namespace App\Http\Livewire;

use App\Models\ConceptoCobro;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ShowConceptosCobro extends Component
{
    use WithPagination;

    protected $listeners = [
        'activarConceptoConfirmado' => 'activar',
        'desactivarConceptoConfirmado' => 'desactivar',
    ];

    private const SORT_COLUMNS = [
        'concepto_cobro_id', 'clave', 'nombre', 'activo', 'orden',
    ];

    public $search = '';
    public $estado = 'todos';
    public $cant = 25;
    public $sort = 'orden';
    public $direction = 'asc';
    public $open_form = false;
    public $editingId;
    public $clave = '';
    public $nombre = '';
    public $descripcion = '';
    public $clave_producto_servicio_sat = '';
    public $clave_unidad_sat = '';
    public $objeto_impuesto_sat = '';
    public $tasa_iva = '';
    public $orden = 0;
    public $activo = true;

    public function mount(): void
    {
        Gate::authorize('manage-conceptos-cobro');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingEstado(): void { $this->resetPage(); }
    public function updatingCant(): void { $this->resetPage(); }

    protected function rules(): array
    {
        return [
            'clave' => [$this->editingId ? 'nullable' : 'required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/', Rule::unique('conceptos_cobro', 'clave')->ignore($this->editingId, 'concepto_cobro_id')],
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string'],
            'clave_producto_servicio_sat' => ['nullable', 'string', 'max:20'],
            'clave_unidad_sat' => ['nullable', 'string', 'max:10'],
            'objeto_impuesto_sat' => ['nullable', 'string', 'max:10'],
            'tasa_iva' => ['nullable', 'numeric', 'between:0,1'],
            'orden' => ['required', 'integer', 'min:0', 'max:65535'],
            'activo' => ['boolean'],
        ];
    }

    public function create(): void
    {
        Gate::authorize('manage-conceptos-cobro');
        $this->resetForm();
        $this->open_form = true;
    }

    public function edit($id): void
    {
        Gate::authorize('manage-conceptos-cobro');
        $concepto = ConceptoCobro::findOrFail($id);
        $this->resetForm();
        $this->editingId = $concepto->getKey();
        foreach (['clave', 'nombre', 'descripcion', 'clave_producto_servicio_sat', 'clave_unidad_sat', 'objeto_impuesto_sat', 'tasa_iva', 'orden', 'activo'] as $field) {
            $this->{$field} = $concepto->{$field} ?? '';
        }
        $this->open_form = true;
    }

    public function store(): void
    {
        Gate::authorize('manage-conceptos-cobro');
        $this->editingId = null;
        $this->clave = strtoupper(trim($this->clave));
        $this->nombre = trim($this->nombre);
        $validated = $this->validate();
        $concepto = new ConceptoCobro();
        $this->applyValidated($concepto, $validated, true);
        $concepto->save();
        $this->finish('El concepto de cobro fue creado satisfactoriamente.');
    }

    public function update(): void
    {
        Gate::authorize('manage-conceptos-cobro');
        $concepto = ConceptoCobro::findOrFail($this->editingId);
        $originalKey = $concepto->clave;
        $this->clave = $originalKey;
        $this->nombre = trim($this->nombre);
        $validated = $this->validate();
        unset($validated['clave']);
        $this->applyValidated($concepto, $validated, false);
        $concepto->save();
        $this->finish('El concepto de cobro fue actualizado satisfactoriamente.');
    }

    public function activar($id): void { $this->setActivo($id, true); }
    public function desactivar($id): void { $this->setActivo($id, false); }

    public function toggleEstado($id): void
    {
        Gate::authorize('manage-conceptos-cobro');
        $concepto = ConceptoCobro::findOrFail($id);
        $this->setActivo($concepto->getKey(), ! $concepto->activo);
    }

    public function closeForm(): void
    {
        $this->resetForm();
        $this->open_form = false;
    }

    public function order($column): void
    {
        if (! in_array($column, self::SORT_COLUMNS, true)) {
            $this->sort = 'orden';
            $this->direction = 'asc';
            return;
        }
        $this->direction = $this->sort === $column && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
    }

    public function render()
    {
        $term = trim($this->search);
        $query = ConceptoCobro::query()
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($query) => $query->where('clave', 'like', $like)->orWhere('nombre', 'like', $like)->orWhere('descripcion', 'like', $like));
            })
            ->when($this->estado === 'activos', fn ($query) => $query->where('activo', true))
            ->when($this->estado === 'inactivos', fn ($query) => $query->where('activo', false));

        if (in_array($this->sort, self::SORT_COLUMNS, true) && in_array($this->direction, ['asc', 'desc'], true)) {
            $query->orderBy($this->sort, $this->direction)->orderBy('nombre');
        } else {
            $this->sort = 'orden';
            $this->direction = 'asc';
            $query->orderBy('orden')->orderBy('nombre');
        }

        $perPage = in_array((int) $this->cant, [10, 25, 50, 100], true) ? (int) $this->cant : 25;

        return view('livewire.show-conceptos-cobro', ['conceptos' => $query->paginate($perPage)]);
    }

    private function setActivo($id, bool $activo): void
    {
        Gate::authorize('manage-conceptos-cobro');
        $concepto = ConceptoCobro::findOrFail($id);
        $concepto->activo = $activo;
        $concepto->save();
        $this->emit('alert', $activo ? 'El concepto fue activado.' : 'El concepto fue desactivado.');
    }

    private function applyValidated(ConceptoCobro $concepto, array $validated, bool $includeKey): void
    {
        $fields = ['nombre', 'descripcion', 'clave_producto_servicio_sat', 'clave_unidad_sat', 'objeto_impuesto_sat', 'tasa_iva', 'orden', 'activo'];
        if ($includeKey) array_unshift($fields, 'clave');
        foreach ($fields as $field) {
            $value = $validated[$field] ?? null;
            $concepto->{$field} = is_string($value) && trim($value) === '' ? null : $value;
        }
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'clave', 'nombre', 'descripcion', 'clave_producto_servicio_sat', 'clave_unidad_sat', 'objeto_impuesto_sat', 'tasa_iva']);
        $this->orden = 0;
        $this->activo = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function finish(string $message): void
    {
        $this->open_form = false;
        $this->resetForm();
        $this->emit('alert', $message);
    }
}
