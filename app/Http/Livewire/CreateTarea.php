<?php

namespace App\Http\Livewire;

use App\Models\EstatuTarea;
use App\Models\Prospecto;
use App\Models\Tarea;
use Livewire\Component;

class CreateTarea extends Component
{
    public $open = false;

    public $tareas_descripcion,$tareas_fecha,$tareas_comentario,$prospectos_id,$est_tareas_id;

    protected $rules = [
        'tareas_descripcion'=>'required|min:10|max:100',
        'tareas_fecha'=>'required|date',
        'tareas_comentario'=>'required|min:10|max:512',
        'prospectos_id'=>'required',
        'est_tareas_id'=>'required',
    ];

    public function save(){
        $this->validate();
        Tarea::create([
            'tareas_descripcion' =>$this->tareas_descripcion,
            'tareas_fecha' =>$this->tareas_fecha,
            'tareas_comentario' =>$this->tareas_comentario,
            'prospectos_id' =>$this->prospectos_id,
            'est_tareas_id' =>$this->est_tareas_id
        ]);
        $this->reset(['open','tareas_descripcion','tareas_fecha','tareas_comentario',
        'prospectos_id','est_tareas_id']);
        $this->emitTo('show-tareas','render');
        $this->emit('alert','La tarea fue agregado satifactoriamente');
    }



    public function render()
    {
        $prospectos = Prospecto::all();
        $estatus = EstatuTarea::all();
        return view('livewire.create-tarea',['prospectos'=>$prospectos
                                            ,'estatus'=>$estatus]);
    }
}
