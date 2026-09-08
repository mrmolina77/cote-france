<?php

namespace App\Http\Livewire;

use App\Models\MetodoPago;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ShowMetodosPago extends Component
{
    use WithPagination;

    private const SORT_COLUMNS = ['metodo_pago_id', 'clave', 'nombre', 'clave_forma_pago_sat', 'activo', 'orden'];
    private const PER_PAGE = [10, 25, 50, 100];
    public const REQUIREMENT_LABELS = [
        'requiere_forma_pago_sat' => 'Forma de pago SAT',
        'requiere_banco' => 'Banco',
        'requiere_referencia' => 'Referencia',
        'requiere_numero_cheque' => 'Número de cheque',
        'requiere_rastreo_spei' => 'Rastreo SPEI',
        'requiere_autorizacion' => 'Autorización',
        'requiere_terminal' => 'Terminal',
        'requiere_ultimos_4_digitos' => 'Últimos 4 dígitos',
        'requiere_proveedor' => 'Proveedor',
        'requiere_anticipo_relacionado' => 'Anticipo relacionado',
        'requiere_comprobante' => 'Comprobante',
    ];

    protected $listeners = ['activarMetodoPagoConfirmado' => 'activar', 'desactivarMetodoPagoConfirmado' => 'desactivar'];

    public $search = '';
    public $estado = 'todos';
    public $cant = 25;
    public $sort = 'orden';
    public $direction = 'asc';
    public $open_form = false;
    public $editingId;
    public $editingSignature;
    public $clave = '';
    public $nombre = '';
    public $descripcion = '';
    public $clave_forma_pago_sat = '';
    public $orden = 0;
    public $activo = true;
    public $requiere_forma_pago_sat = false;
    public $requiere_banco = false;
    public $requiere_referencia = false;
    public $requiere_numero_cheque = false;
    public $requiere_rastreo_spei = false;
    public $requiere_autorizacion = false;
    public $requiere_terminal = false;
    public $requiere_ultimos_4_digitos = false;
    public $requiere_proveedor = false;
    public $requiere_anticipo_relacionado = false;
    public $requiere_comprobante = false;

    public function mount(): void { Gate::authorize('manage-metodos-pago'); }
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingEstado(): void { $this->resetPage(); }
    public function updatingCant(): void { $this->resetPage(); }

    protected function rules(): array
    {
        $rules = [
            'clave' => [$this->editingId ? 'nullable' : 'required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/', Rule::unique('metodos_pago', 'clave')->ignore($this->editingId, 'metodo_pago_id')],
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string'],
            'clave_forma_pago_sat' => ['nullable', 'string', 'regex:/^\d{2}$/'],
            'orden' => ['required', 'integer', 'min:0', 'max:65535'],
            'activo' => ['boolean'],
        ];
        foreach (array_keys(self::REQUIREMENT_LABELS) as $field) $rules[$field] = ['boolean'];
        return $rules;
    }

    public function create(): void
    {
        Gate::authorize('manage-metodos-pago');
        $this->resetForm();
        $this->open_form = true;
    }

    public function edit($id): void
    {
        Gate::authorize('manage-metodos-pago');
        $metodo = MetodoPago::findOrFail($id);
        $this->resetForm();
        $this->editingId = $metodo->getKey();
        $this->editingSignature = $this->signature($metodo->getKey());
        foreach (array_merge(['clave', 'nombre', 'descripcion', 'clave_forma_pago_sat', 'orden', 'activo'], array_keys(self::REQUIREMENT_LABELS)) as $field) {
            $this->{$field} = $metodo->{$field} ?? '';
        }
        $this->open_form = true;
    }

    public function store(): void
    {
        Gate::authorize('manage-metodos-pago');
        $this->editingId = null;
        $this->normalize();
        $validated = $this->validate();
        $metodo = new MetodoPago();
        $this->applyValidated($metodo, $validated, true);
        $metodo->save();
        $this->finish('El método de pago fue creado satisfactoriamente.');
    }

    public function update(): void
    {
        Gate::authorize('manage-metodos-pago');
        $metodo = MetodoPago::find($this->editingId);
        abort_unless($metodo && hash_equals($this->signature($metodo->getKey()), (string) $this->editingSignature), 404);
        $this->clave = $metodo->clave;
        $this->normalize();
        $validated = $this->validate();
        unset($validated['clave']);
        $this->applyValidated($metodo, $validated, false);
        $metodo->save();
        $this->finish('El método de pago fue actualizado satisfactoriamente.');
    }

    public function activar($id): void { $this->setActivo($id, true); }
    public function desactivar($id): void { $this->setActivo($id, false); }

    public function toggleEstado($id): void
    {
        Gate::authorize('manage-metodos-pago');
        $metodo = MetodoPago::findOrFail($id);
        $this->setActivo($metodo->getKey(), ! $metodo->activo);
    }

    public function closeForm(): void { $this->resetForm(); $this->open_form = false; }

    public function order($column): void
    {
        if (! in_array($column, self::SORT_COLUMNS, true)) {
            $this->sort = 'orden'; $this->direction = 'asc'; return;
        }
        $this->direction = $this->sort === $column && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
    }

    public function render()
    {
        $term = trim($this->search);
        $query = MetodoPago::query()
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($query) => $query->where('clave', 'like', $like)->orWhere('nombre', 'like', $like)->orWhere('descripcion', 'like', $like)->orWhere('clave_forma_pago_sat', 'like', $like));
            })
            ->when($this->estado === 'activos', fn ($query) => $query->where('activo', true))
            ->when($this->estado === 'inactivos', fn ($query) => $query->where('activo', false));
        if (! in_array($this->sort, self::SORT_COLUMNS, true) || ! in_array($this->direction, ['asc', 'desc'], true)) {
            $this->sort = 'orden'; $this->direction = 'asc';
        }
        $query->orderBy($this->sort, $this->direction)->orderBy('nombre', 'asc');
        $perPage = in_array((int) $this->cant, self::PER_PAGE, true) ? (int) $this->cant : 25;
        return view('livewire.show-metodos-pago', ['metodos' => $query->paginate($perPage), 'requirementLabels' => self::REQUIREMENT_LABELS]);
    }

    private function setActivo($id, bool $activo): void
    {
        Gate::authorize('manage-metodos-pago');
        $metodo = MetodoPago::findOrFail($id);
        $metodo->activo = $activo;
        $metodo->save();
        $this->emit('alert', $activo ? 'El método de pago fue activado.' : 'El método de pago fue desactivado.');
    }

    private function normalize(): void
    {
        $this->clave = strtoupper(trim((string) $this->clave));
        $this->nombre = trim((string) $this->nombre);
        $this->descripcion = trim((string) $this->descripcion);
        $this->clave_forma_pago_sat = trim((string) $this->clave_forma_pago_sat);
    }

    private function applyValidated(MetodoPago $metodo, array $validated, bool $includeKey): void
    {
        $fields = array_merge(['nombre', 'descripcion', 'clave_forma_pago_sat', 'orden', 'activo'], array_keys(self::REQUIREMENT_LABELS));
        if ($includeKey) array_unshift($fields, 'clave');
        foreach ($fields as $field) {
            $value = $validated[$field];
            $metodo->{$field} = is_string($value) && $value === '' ? null : $value;
        }
    }

    private function signature($id): string { return hash_hmac('sha256', (string) $id, (string) config('app.key')); }

    private function resetForm(): void
    {
        $this->reset(array_merge(['editingId', 'editingSignature', 'clave', 'nombre', 'descripcion', 'clave_forma_pago_sat'], array_keys(self::REQUIREMENT_LABELS)));
        $this->orden = 0; $this->activo = true;
        $this->resetErrorBag(); $this->resetValidation();
    }

    private function finish(string $message): void
    {
        $this->open_form = false; $this->resetForm(); $this->emit('alert', $message);
    }
}
