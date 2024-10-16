<?php

namespace App\Http\Livewire;

use App\Models\Grupo;
use App\Models\Estado;
use App\Models\Modalidad;
use Livewire\Component;

class CreateGrupos extends Component
{
    public $open = false;

    public $grupo_nombre,$grupo_nivel,$grupo_capitulo;
    public $grupo_libro_maestro,$grupo_libro_alumno,$grupo_observacion,$modalidad_id;
    public $estado_id;

    protected $rules = [
        'grupo_nombre'=>'required|min:3|max:50',
        'grupo_nivel'=>'required|min:3|max:50',
        'grupo_capitulo'=>'required|numeric',
        'grupo_libro_maestro'=>'required|min:7|max:255',
        'grupo_libro_alumno'=>'required|min:7|max:255',
        'grupo_observacion'=>'required|min:7|max:255',
        'modalidad_id'=>'required',
        'estado_id'=>'required',
    ];



    /* public function updated($propertyName){
        $this->validateOnly($propertyName);
    } */

    public function save(){
        $this->validate();
        $prospecto = Grupo::create([
            'grupo_nombre' =>$this->grupo_nombre,
            'grupo_nivel' =>$this->grupo_nivel,
            'grupo_capitulo' =>$this->grupo_capitulo,
            'grupo_libro_maestro' =>$this->grupo_libro_maestro,
            'grupo_libro_alumno' =>$this->grupo_libro_alumno,
            'grupo_observacion' =>$this->grupo_observacion,
            'modalidad_id' =>$this->modalidad_id,
            'estado_id' =>$this->estado_id
        ]);

        $this->reset(['open','grupo_nombre','grupo_nivel','grupo_capitulo',
        'grupo_libro_maestro','grupo_libro_alumno','grupo_observacion','modalidad_id',
        'estado_id']);
        $this->emitTo('show-grupos','render');
        $this->emit('alert','El grupo fue agregado satifactoriamente');
    }

    public function render()
    {

        $modalidades = Modalidad::all();
        $estados = Estado::all();
        return view('livewire.create-grupos',['modalidades'=>$modalidades
                                             ,'estados'=>$estados]);
    }
}
