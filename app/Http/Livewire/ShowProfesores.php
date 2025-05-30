<?php

namespace App\Http\Livewire;

use App\Models\BloqueosProfesores;
use App\Models\Dia;
use App\Models\Modalidad;
use App\Models\Hora;
use App\Models\Profesor;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ShowProfesores extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'profesores_id';
    public $direction = 'asc';
    public $profesor;
    public $cant = 25;
    public $readyToLoad = false;
    public $horasDisponibles;
    public $horaAll;

    public $open_edit = false;
    protected $listeners = ['render','delete'];

    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->horasDisponibles = collect([]);
        $this->horaAll = Hora::all();
    }

    // Propiedades para manejar los bloqueos en el formulario de EDICIÓN
    public $newRecurringBlockForUpdate = [
        'dayOfWeek' => '',
        'horas_id' => '',
    ];
    public $currentRecurringBlocks = []; // Almacenará los bloques recurrentes del profesor actual y los nuevos añadidos en el form de edición

    public $newFullDayBlockDateForUpdate = '';
    public $currentFullDayBlocks = []; // Almacenará los días completos bloqueados del profesor actual y los nuevos

    protected $rules = [
        'profesor.profesores_nombres'=>'required|min:3|max:100',
        'profesor.profesores_apellidos'=>'required|min:3|max:100',
        'profesor.profesores_email'=>'required|email',
        'profesor.profesores_fecha_ingreso'=>'required|date',
        'profesor.profesores_horas_semanales'=>'integer',
        'profesor.profesores_color'=>'required',
        'profesor.modalidad_id'=>'required',
        // Reglas para los nuevos bloqueos (se validan al añadir, no en el save general)
    ];

    protected $messages = [
        'newRecurringBlockForUpdate.dayOfWeek.required' => 'El día de la semana es obligatorio.',
        'newRecurringBlockForUpdate.dayOfWeek.integer' => 'El día de la semana debe ser un número.',
        'newRecurringBlockForUpdate.dayOfWeek.min' => 'El día de la semana no es válido.',
        'newRecurringBlockForUpdate.dayOfWeek.max' => 'El día de la semana no es válido.',
        'newRecurringBlockForUpdate.horas_id.required' => 'El rango de horas es obligatorio.',
        'newRecurringBlockForUpdate.horas_id.exists' => 'El rango de horas seleccionado no es válido.',
        'newRecurringBlockForUpdate.endTime.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        'newFullDayBlockDateForUpdate.required' => 'La fecha es obligatoria.',
        'newFullDayBlockDateForUpdate.date' => 'La fecha no es válida.',
    ];

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){
            // $clasespruebas = ClasePrueba::orderBy($this->sort,$this->direction)
            //                             ->paginate($this->cant);
            $profesores = DB::table('profesores')
            ->select('profesores.profesores_nombres','profesores.profesores_apellidos'
                    ,'profesores.profesores_email','profesores.profesores_id'
                    ,'profesores.profesores_fecha_ingreso','profesores.profesores_color'
                    ,'modalidades.modalidad_nombre')
            ->join('modalidades','profesores.modalidad_id','=','modalidades.modalidad_id')
            ->orWhere('profesores.profesores_nombres','like','%'.trim($this->search).'%')
            ->orWhere('profesores.profesores_apellidos','like','%'.trim($this->search).'%')
            ->orWhere('profesores.profesores_email','like','%'.trim($this->search).'%')
            ->paginate($this->cant);
            // $prospectos = Prospecto::where('prospectos_nombres','like','%'.trim($this->search).'%')
            //                        ->orWhere('prospectos_apellidos','like','%'.trim($this->search).'%')
            //                        ->orderBy($this->sort,$this->direction)
            //                        ->paginate($this->cant);
        } else {
            $profesores = array();
        }

        $modalidades = Modalidad::all();
        $dias = Dia::all();

        return view('livewire.show-profesores',['profesores'=>$profesores
                                               ,'modalidades'=>$modalidades
                                               ,'dias'=>$dias]);
    }

    public function loadPosts(){
        $this->readyToLoad = true;
    }

    public function order($order){
        if ($this->sort== $order) {
            if ($this->direction == 'desc') {
                $this->direction = 'asc';
            } else {
                $this->direction = 'desc';
            }
        } else {
            $this->sort= $order;
            $this->direction = 'asc';
        }
    }

    public function edit($id){
        $profesor = Profesor::find($id);
        $this->profesor = $profesor;
        $this->open_edit = true;

        // Cargar los bloqueos existentes del profesor
        $this->currentRecurringBlocks = [];
        $this->currentFullDayBlocks = [];

        if ($this->profesor) {
            // Cargar la relación 'bloqueos' y luego 'hora' y 'dia' para cada bloqueo
            $this->profesor->load(['bloqueos.hora', 'bloqueos.dia']);

            foreach ($this->profesor->bloqueos as $bloqueo) {
                if ($bloqueo->dias_id && $bloqueo->horas_id && $bloqueo->hora) { // Bloqueo recurrente
                    $this->currentRecurringBlocks[] = [
                        'id' => $bloqueo->bloqueo_id,
                        'dayOfWeek' => $bloqueo->dias_id, // Asumiendo que dias_id (1-7) corresponde a dayOfWeek
                        'horas_id' => $bloqueo->horas_id, // Guardamos el horas_id
                        'is_new' => false,
                        // 'motivo' => $bloqueo->motivo, // Si tienes un campo motivo
                    ];
                } elseif ($bloqueo->fecha) { // Bloqueo de día completo
                    $this->currentFullDayBlocks[] = [
                        'id' => $bloqueo->bloqueo_id,
                        'date' => \Carbon\Carbon::parse($bloqueo->fecha)->format('Y-m-d'),
                        'is_new' => false,
                        // 'motivo' => $bloqueo->motivo, // Si tienes un campo motivo
                    ];
                }
            }
        }

        // Limpiar los campos de "nuevo bloqueo"
        $this->resetValidation(); // Limpia errores de validación previos
        $this->newRecurringBlockForUpdate = ['dayOfWeek' => '', 'horas_id' => ''];
        $this->newFullDayBlockDateForUpdate = '';
    }

    public function update(){
        $this->profesor->save();
        $this->reset(['open_edit']);
        $this->emit('alert','El profesor fue modificado satifactoriamente');

        // Lógica para actualizar los bloqueos:
        if ($this->profesor) {
            // 1. Eliminar todos los bloqueos existentes para este profesor
            BloqueosProfesores::where('profesor_id', $this->profesor->profesores_id)->delete();

            // 2. Crear nuevos bloqueos recurrentes
            foreach ($this->currentRecurringBlocks as $block) {
                // Usamos directamente el horas_id seleccionado
                BloqueosProfesores::create([
                    'profesor_id' => $this->profesor->profesores_id,
                    'dias_id' => $block['dayOfWeek'], // Asume que dias_id (1-7) es lo que se guarda
                    'horas_id' => $block['horas_id'],
                    'fecha' => null,
                    // 'motivo' => $block['motivo'] ?? null, // Si tienes motivo
                ]);
            }

            // 3. Crear nuevos bloqueos de día completo
            foreach ($this->currentFullDayBlocks as $block) {
                BloqueosProfesores::create([
                    'profesor_id' => $this->profesor->profesores_id,
                    'dias_id' => null,
                    'horas_id' => null,
                    'fecha' => $block['date'],
                    // 'motivo' => $block['motivo'] ?? null, // Si tienes motivo
                ]);
            }
        }

        $this->reset([
            'open_edit',
            'newRecurringBlockForUpdate',
            'currentRecurringBlocks',
            'newFullDayBlockDateForUpdate',
            'currentFullDayBlocks'
        ]);
        $this->newRecurringBlockForUpdate = ['dayOfWeek' => '', 'horas_id' => ''];
        $this->emit('alert', __('Teacher and blocks updated successfully'));
        $this->emitTo('show-profesores', 'render');
    }

    public function delete(Profesor $profesor){
        $profesor->delete();
        $this->emit('alert','El profesor fue eliminado satifactoriamente');
    }
    // Métodos para añadir/quitar bloques en el formulario de EDICIÓN
    public function addRecurringBlockForUpdate()
    {
        $this->validate([
            'newRecurringBlockForUpdate.dayOfWeek' => 'required|integer|min:1|max:7',
            'newRecurringBlockForUpdate.horas_id' => 'required|exists:horas,horas_id',
        ], [], [
            'newRecurringBlockForUpdate.dayOfWeek' => __('Day of Week'),
            'newRecurringBlockForUpdate.horas_id' => __('Time Slot'),
        ]);

        // Opcional: Verificar duplicados en la UI antes de añadir
        // foreach ($this->currentRecurringBlocks as $existingBlock) {
        // if ($existingBlock['dayOfWeek'] == $this->newRecurringBlockForUpdate['dayOfWeek'] &&
        //     $existingBlock['startTime'] == $this->newRecurringBlockForUpdate['startTime'] &&
        //     $existingBlock['endTime'] == $this->newRecurringBlockForUpdate['endTime']) {
        //         $this->addError('newRecurringBlockForUpdate.dayOfWeek', __('This recurring block already exists.'));
        // return;
        //     }
        // }

        $this->currentRecurringBlocks[] = [
            'dayOfWeek' => $this->newRecurringBlockForUpdate['dayOfWeek'],
            'horas_id' => $this->newRecurringBlockForUpdate['horas_id'],
            'is_new' => true,
            'id' => null
        ];
        $this->newRecurringBlockForUpdate = ['dayOfWeek' => '', 'horas_id' => ''];
        $this->resetValidation('newRecurringBlockForUpdate.*');
    }

    public function removeRecurringBlockForUpdate($index)
    {
        if (isset($this->currentRecurringBlocks[$index])) {
            unset($this->currentRecurringBlocks[$index]);
            $this->currentRecurringBlocks = array_values($this->currentRecurringBlocks);
        }
    }

    public function addFullDayBlockForUpdate()
    {
        $this->validate([
            'newFullDayBlockDateForUpdate' => 'required|date',
        ], [], [
            'newFullDayBlockDateForUpdate' => __('Date'),
        ]);

        $this->currentFullDayBlocks[] = ['date' => $this->newFullDayBlockDateForUpdate, 'is_new' => true, 'id' => null];
        $this->newFullDayBlockDateForUpdate = '';
        $this->resetValidation('newFullDayBlockDateForUpdate');
    }

    public function removeFullDayBlockForUpdate($index)
    {
        if (isset($this->currentFullDayBlocks[$index])) {
            unset($this->currentFullDayBlocks[$index]);
            $this->currentFullDayBlocks = array_values($this->currentFullDayBlocks);
        }
    }

    public function updatedNewRecurringBlockForUpdateDayOfWeek($selectedDayId)
    {
        if ($selectedDayId == 6) {
            $this->horasDisponibles = Hora::where('tipo', 2)->orderBy('horas_desde')->get();
        } else {
            $this->horasDisponibles = Hora::where('tipo', 1)->orderBy('horas_desde')->get();
        }
    }

}
