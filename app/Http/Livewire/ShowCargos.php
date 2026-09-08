<?php

namespace App\Http\Livewire;

use DateTimeImmutable;
use App\Exceptions\CargoManualInvalidoException;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Inscripcion;
use App\Services\Facturacion\CreadorCargoManualService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ShowCargos extends Component
{
    use WithPagination;

    private const SORT_COLUMNS = ['cargo_id', 'fecha_emision', 'fecha_vencimiento', 'periodo_anio', 'periodo_mes', 'total', 'saldo_pendiente', 'estado', 'origen'];

    public $search = '';
    public $estado = 'todos';
    public $origen = 'todos';
    public $concepto = 'todos';
    public $periodo_anio_filtro = '';
    public $periodo_mes_filtro = '';
    public $vencimiento_desde = '';
    public $vencimiento_hasta = '';
    public $cant = 25;
    public $sort = 'fecha_vencimiento';
    public $direction = 'desc';
    public $open_form = false;
    public $busqueda_inscripcion = '';
    public $inscripciones_id = '';
    public $concepto_cobro_id = '';
    public $fecha_emision = '';
    public $fecha_vencimiento = '';
    public $subtotal = '';
    public $periodo_anio = '';
    public $periodo_mes = '';
    public $observaciones = '';

    public function mount(): void
    {
        Gate::authorize('manage-cargos');
    }

    public function updating($name): void
    {
        Gate::authorize('manage-cargos');
        if (in_array($name, ['search', 'estado', 'origen', 'concepto', 'periodo_anio_filtro', 'periodo_mes_filtro', 'vencimiento_desde', 'vencimiento_hasta', 'cant'], true)) $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'inscripciones_id' => ['required', 'integer', Rule::exists('inscripciones', 'inscripciones_id')->whereNull('deleted_at')],
            'concepto_cobro_id' => ['required', 'integer', Rule::exists('conceptos_cobro', 'concepto_cobro_id')->where(fn ($query) => $query->where('activo', true)->whereNotIn('clave', CreadorCargoManualService::CONCEPTOS_RESERVADOS))],
            'fecha_emision' => ['required', 'date_format:Y-m-d'],
            'fecha_vencimiento' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_emision'],
            'subtotal' => ['required', 'string', 'regex:/^(?:0|[1-9]\d{0,9})(?:\.\d{1,2})?$/'],
            'periodo_anio' => ['nullable', 'integer', 'between:1,65535', 'required_with:periodo_mes'],
            'periodo_mes' => ['nullable', 'integer', 'between:1,12', 'required_with:periodo_anio'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'concepto_cobro_id.exists' => 'El concepto debe estar activo y permitido para cargos manuales.',
            'subtotal.regex' => 'El importe debe ser mayor que cero, sin separadores y con máximo dos decimales.',
            'periodo_anio.required_with' => 'El año y el mes del periodo deben proporcionarse juntos.',
            'periodo_mes.required_with' => 'El año y el mes del periodo deben proporcionarse juntos.',
        ];
    }

    public function create(): void
    {
        Gate::authorize('manage-cargos');
        $this->resetForm();
        $this->fecha_emision = now()->toDateString();
        $this->fecha_vencimiento = now()->toDateString();
        $this->open_form = true;
    }

    public function closeForm(): void
    {
        Gate::authorize('manage-cargos');
        $this->open_form = false;
        $this->resetForm();
    }

    public function store(CreadorCargoManualService $servicio): void
    {
        Gate::authorize('manage-cargos');
        $datos = $this->validate();
        if ($this->importeEsCeroOSuperiorAlMaximo($datos['subtotal'])) {
            $this->addError('subtotal', 'El importe debe estar entre 0.01 y 9,999,999,999.99.');
            return;
        }
        try {
            $servicio->crear($datos, Auth::id());
        } catch (CargoManualInvalidoException $exception) {
            $this->addError($exception->campo(), $exception->getMessage());
            return;
        }
        $this->open_form = false;
        $this->resetForm();
        $this->emit('alert', 'El cargo extraordinario fue creado satisfactoriamente.');
    }

    public function order($column): void
    {
        Gate::authorize('manage-cargos');
        if (! in_array($column, self::SORT_COLUMNS, true)) {
            $this->sort = 'fecha_vencimiento';
            $this->direction = 'desc';
            return;
        }
        $this->direction = $this->sort === $column && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('manage-cargos');
        $term = trim($this->search);
        $query = Cargo::query()->with(['inscripcion.prospecto', 'conceptoCobro', 'createdBy'])
            ->when($term !== '', function (Builder $query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(function (Builder $query) use ($term, $like) {
                    if (ctype_digit($term)) $query->orWhere('cargo_id', (int) $term);
                    $query->orWhere('observaciones', 'like', $like)
                        ->orWhereHas('conceptoCobro', fn (Builder $conceptos) => $conceptos->where('nombre', 'like', $like)->orWhere('clave', 'like', $like))
                        ->orWhereHas('inscripcion.prospecto', function (Builder $prospectos) use ($term) {
                            foreach (preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY) as $parte) {
                                $like = '%'.$parte.'%';
                                $prospectos->where(fn (Builder $nombre) => $nombre
                                    ->where('prospectos_nombres', 'like', $like)
                                    ->orWhere('prospectos_apellidos', 'like', $like));
                            }
                        });
                });
            })
            ->when(in_array($this->estado, Cargo::ESTADOS, true), fn (Builder $query) => $query->where('estado', $this->estado))
            ->when(in_array($this->origen, Cargo::ORIGENES, true), fn (Builder $query) => $query->where('origen', $this->origen))
            ->when(ctype_digit((string) $this->concepto), fn (Builder $query) => $query->where('concepto_cobro_id', (int) $this->concepto))
            ->when(ctype_digit((string) $this->periodo_anio_filtro), fn (Builder $query) => $query->where('periodo_anio', (int) $this->periodo_anio_filtro))
            ->when(ctype_digit((string) $this->periodo_mes_filtro), fn (Builder $query) => $query->where('periodo_mes', (int) $this->periodo_mes_filtro))
            ->when($this->fechaValida($this->vencimiento_desde), fn (Builder $query) => $query->whereDate('fecha_vencimiento', '>=', $this->vencimiento_desde))
            ->when($this->fechaValida($this->vencimiento_hasta), fn (Builder $query) => $query->whereDate('fecha_vencimiento', '<=', $this->vencimiento_hasta));

        if (! in_array($this->sort, self::SORT_COLUMNS, true) || ! in_array($this->direction, ['asc', 'desc'], true)) {
            $this->sort = 'fecha_vencimiento'; $this->direction = 'desc';
        }
        $query->orderBy($this->sort, $this->direction)->orderByDesc('cargo_id');
        $perPage = in_array((int) $this->cant, [10, 25, 50, 100], true) ? (int) $this->cant : 25;

        return view('livewire.show-cargos', [
            'cargos' => $query->paginate($perPage),
            'conceptosFiltro' => ConceptoCobro::query()->activos()->whereNotIn('clave', CreadorCargoManualService::CONCEPTOS_RESERVADOS)->ordenados()->get(['concepto_cobro_id', 'clave', 'nombre']),
            'conceptosManuales' => ConceptoCobro::query()->activos()->whereNotIn('clave', CreadorCargoManualService::CONCEPTOS_RESERVADOS)->ordenados()->get(['concepto_cobro_id', 'clave', 'nombre']),
            'inscripciones' => $this->inscripcionesDisponibles(),
        ]);
    }

    private function inscripcionesDisponibles()
    {
        if (! $this->open_form) return collect();
        $term = trim($this->busqueda_inscripcion);
        return Inscripcion::query()->with(['prospecto', 'grupo', 'cursos'])
            ->whereHas('prospecto')->whereHas('grupo')->whereHas('cursos')
            ->when($term !== '', function (Builder $query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(function (Builder $query) use ($term, $like) {
                    if (ctype_digit($term)) $query->orWhere('inscripciones_id', (int) $term);
                    $query->orWhereHas('prospecto', fn (Builder $prospectos) => $prospectos->where('prospectos_nombres', 'like', $like)->orWhere('prospectos_apellidos', 'like', $like));
                });
            })->latest('inscripciones_id')->limit(25)->get();
    }

    private function resetForm(): void
    {
        $this->reset(['busqueda_inscripcion', 'inscripciones_id', 'concepto_cobro_id', 'fecha_emision', 'fecha_vencimiento', 'subtotal', 'periodo_anio', 'periodo_mes', 'observaciones']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function importeEsCeroOSuperiorAlMaximo(string $importe): bool
    {
        [$entero, $decimal] = array_pad(explode('.', $importe, 2), 2, '');
        $normalizado = $entero.'.'.str_pad($decimal, 2, '0');
        return $normalizado === '0.00' || (strlen($entero) === 10 && strcmp($normalizado, '9999999999.99') > 0);
    }

    private function fechaValida($fecha): bool
    {
        if (! is_string($fecha) || preg_match('/^\d{4}-\d{2}-\d{2}$/D', $fecha) !== 1) return false;

        $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        return $valor !== false
            && DateTimeImmutable::getLastErrors() === false
            && $valor->format('Y-m-d') === $fecha;
    }
}
