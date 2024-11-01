<?php

namespace App\Http\Livewire;

use App\Models\Espacio;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario;
use Livewire\Component;

class ShowHorarios extends Component
{

    public $fecha;
    public $open_edit;
    public $horarios_dia,$espacios_id,$horas_id,$grupo_id;
    protected $listeners = ['render','delete'];

    protected $rules = [
        'grupo_id'=>'required',
    ];

    public function boot()
    {
        $this->fecha = date('Y-m-d');
    }

    public function render()
    {
        $espacios = Espacio::all();
        $horas = Hora::all();
        $horarios = Horario::whereDate('horarios_dia', $this->fecha)
        ->orderBy('espacios_id', 'asc')
        ->orderBy('horas_id', 'asc')
        ->get();
        $array_horario = array();
        foreach ($horarios as $horario) {
            $array_horario[$horario->horarios_dia][$horario->horas_id][$horario->espacios_id] = [ 'nombre'=>$horario->grupo->grupo_nombre
                                                                                                 ,'color'=>$horario->grupo->profesor->profesores_color
                                                                                                 ,'id'=>$horario->horarios_id
                                                                                                ];
        }
        $grupos = Grupo::all();
        return view('livewire.show-horarios',[
                                            'espacios'=>$espacios
                                           ,'horas'=>$horas
                                           ,'horarios'=>$array_horario
                                           ,'grupos'=>$grupos
                                           ,'fecha'=>$this->fecha
                                            ]);
    }

    public function edit($horarios_dia,$espacios_id,$horas_id){
        $this->horarios_dia = $horarios_dia;
        $this->espacios_id = $espacios_id;
        $this->horas_id = $horas_id;
        $this->open_edit = true;
    }

    public function save(){
        $this->validate();
        $prospecto = Horario::create([
            'horarios_dia' =>$this->horarios_dia,
            'espacios_id' =>$this->espacios_id,
            'horas_id' =>$this->horas_id,
            'grupo_id' =>$this->grupo_id
        ]);

        $this->reset(['open_edit','horarios_dia','espacios_id','grupo_id',
        'horas_id']);
        $this->emitTo('show-horarios','render');
        $this->emit('alert','El horario fue agregado satifactoriamente');
    }

    public function delete(Horario $horario){
        $horario->delete();
        $this->emit('alert','El horario fue eliminado satifactoriamente');
    }
}
