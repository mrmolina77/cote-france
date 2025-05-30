<?php

namespace App\Http\Livewire;

use App\Models\BloqueosProfesores;
use App\Models\Dia;
use App\Models\Hora;
use App\Models\Modalidad;
use App\Models\Profesor;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateProfesores extends Component
{
    public $open = false;

    public $profesores_nombres,$profesores_apellidos, $profesores_email, $profesores_color;
    public $profesores_fecha_ingreso, $profesores_horas_semanales, $modalidad_id;
    public $horasDisponibles; // Almacena las modalidades disponibles

    // Propiedades para el nuevo bloqueo recurrente
    public $newRecurringBlock = [
        'dayOfWeek' => '',
        'horas_id' => '',
    ];
    public $recurringBlocks = []; // Almacenará los bloques recurrentes añadidos temporalmente
    public $newFullDayBlockDate = '';
    public $fullDayBlocks = []; // Almacenará las fechas de bloqueo completo añadidas temporalmente

    protected $rules = [
        'profesores_nombres'=>'required|min:3|max:100',
        'profesores_apellidos'=>'required|min:3|max:100',
        'profesores_email'=>'required|email',
        'profesores_fecha_ingreso'=>'required|date',
        'profesores_horas_semanales'=>'integer',
        'profesores_color'=>'required',
        'modalidad_id'=>'required',
    ];

    // Mensajes de validación personalizados para los campos de bloqueo
    // Se usarán en los métodos add...Block()
    protected function messagesForBlocks()
    {
        return [
            'newRecurringBlock.dayOfWeek.required' => 'El día de la semana es obligatorio.',
            'newRecurringBlock.horas_id.required' => 'El rango de horas es obligatorio.',
            'newRecurringBlock.horas_id.exists' => 'El rango de horas seleccionado no es válido.',
            'newFullDayBlockDate.required' => 'La fecha es obligatoria.',
            'newFullDayBlockDate.date' => 'La fecha no es válida.',
            'newFullDayBlockDate.unique' => 'Esta fecha ya ha sido añadida para bloqueo.',
        ];
    }

    protected function attributesForBlocks()
    {
        return [
            'newRecurringBlock.dayOfWeek' => __('Day of Week'),
            'newRecurringBlock.horas_id' => __('Time Slot'),
            'newFullDayBlockDate' => __('Date'),
        ];
    }

    public function boot()
    {
        $this->profesores_fecha_ingreso = date('Y-m-d');
        $this->newRecurringBlock = ['dayOfWeek' => '', 'horas_id' => ''];
        $this->recurringBlocks = [];
        $this->newFullDayBlockDate = '';
        $this->fullDayBlocks = [];
        $this->horasDisponibles = collect([]);
    }

    public function save(){
        $this->validate();

        DB::beginTransaction();
        try {
            $profesor = Profesor::create([
                'profesores_nombres' =>$this->profesores_nombres,
                'profesores_apellidos' =>$this->profesores_apellidos,
                'profesores_email' =>$this->profesores_email,
                'profesores_color' =>$this->profesores_color,
                'modalidad_id' =>$this->modalidad_id,
                'profesores_fecha_ingreso' =>$this->profesores_fecha_ingreso,
                'profesores_horas_semanales' =>$this->profesores_horas_semanales
            ]);

            // Guardar bloqueos recurrentes
            foreach ($this->recurringBlocks as $block) {
                BloqueosProfesores::create([
                    'profesor_id' => $profesor->profesores_id,
                    'dias_id' => $block['dayOfWeek'],
                    'horas_id' => $block['horas_id'],
                    'fecha' => null,
                    // 'motivo' => $block['motivo'] ?? null, // Si añades un campo motivo
                ]);
            }

            // Guardar bloqueos de día completo
            foreach ($this->fullDayBlocks as $block) { // Asumiendo que $fullDayBlocks almacena arrays ['date' => 'YYYY-MM-DD']
                BloqueosProfesores::create([
                    'profesor_id' => $profesor->profesores_id,
                    'dias_id' => null,
                    'horas_id' => null,
                    'fecha' => $block['date'],
                    // 'motivo' => $block['motivo'] ?? null, // Si añades un campo motivo
                ]);
            }

            DB::commit();

            $this->reset(['open','profesores_nombres','profesores_apellidos','profesores_email','profesores_fecha_ingreso',
            'profesores_color','profesores_horas_semanales','modalidad_id',
            'newRecurringBlock', 'recurringBlocks', 'newFullDayBlockDate', 'fullDayBlocks']);
            $this->newRecurringBlock = ['dayOfWeek' => '', 'horas_id' => ''];
            $this->recurringBlocks = [];
            $this->newFullDayBlockDate = '';
            $this->fullDayBlocks = [];

            $this->emitTo('show-profesores','render');
            $this->emit('alert','El profesor y sus bloqueos fueron agregados satisfactoriamente');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->emit('alert', 'Error al guardar el profesor o sus bloqueos: ' . $e->getMessage(), 'Error!', 'error');
        }
    }

    public function render()
    {
        $modalidades = Modalidad::all();

        $dias = Dia::all();
        return view('livewire.create-profesores', [
            'modalidades' => $modalidades,
            'dias' => $dias
        ]);
    }

    public function addRecurringBlock()
    {
        $this->validate([
            'newRecurringBlock.dayOfWeek' => 'required|integer|min:1|max:7',
            'newRecurringBlock.horas_id' => 'required|exists:horas,horas_id',
        ], $this->messagesForBlocks(), $this->attributesForBlocks());

        $this->recurringBlocks[] = $this->newRecurringBlock;
        $this->newRecurringBlock = ['dayOfWeek' => '', 'horas_id' => ''];
        $this->resetValidation('newRecurringBlock.*');
    }

    public function removeRecurringBlock($index)
    {
        if (isset($this->recurringBlocks[$index])) {
            unset($this->recurringBlocks[$index]);
            $this->recurringBlocks = array_values($this->recurringBlocks); // Reindexar
        }
    }

    public function addFullDayBlock()
    {
        $this->validate([
            'newFullDayBlockDate' => 'required|date',
        ], $this->messagesForBlocks(), $this->attributesForBlocks());

        // Evitar duplicados en la UI
        foreach ($this->fullDayBlocks as $block) {
            if ($block['date'] === $this->newFullDayBlockDate) {
                $this->addError('newFullDayBlockDate', __('This date is already selected for blocking.'));
                return;
            }
        }

        $this->fullDayBlocks[] = ['date' => $this->newFullDayBlockDate];
        $this->newFullDayBlockDate = '';
        $this->resetValidation('newFullDayBlockDate');
    }

    public function removeFullDayBlock($index)
    {
        if (isset($this->fullDayBlocks[$index])) {
            unset($this->fullDayBlocks[$index]);
            $this->fullDayBlocks = array_values($this->fullDayBlocks); // Reindexar
        }
    }

    public function updatedNewRecurringBlockDayOfWeek($selectedDayId)
    {
        if ($selectedDayId == 6) {
            $this->horasDisponibles = Hora::where('tipo', 2)->orderBy('horas_desde')->get();
        } else {
            $this->horasDisponibles = Hora::where('tipo', 1)->orderBy('horas_desde')->get();
        }
    }

}
