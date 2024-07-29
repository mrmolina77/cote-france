<?php

namespace App\Http\Livewire;

use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Prospecto;
use Livewire\Component;

class CreateInscripciones extends Component
{
    public $open = false;

    public $fecha_inscripcion,$prospectos_id, $cursos_id;

    protected $rules = [
        'prospectos_id'=>'required',
        'cursos_id'=>'required',
        'fecha_inscripcion'=>'required|date',
    ];

    public function save(){
        $this->validate();
        Inscripcion::create([
            'prospectos_id' =>$this->prospectos_id,
            'cursos_id' =>$this->cursos_id,
            'fecha_inscripcion' =>$this->fecha_inscripcion
        ]);
        $this->reset(['open','prospectos_id','cursos_id','fecha_inscripcion']);
        $this->emitTo('show-inscripciones','render');
        $this->emit('alert','La inscripción fue agregado satifactoriamente');
    }

    public function render()
    {
        $prospectos = Prospecto::all();
        $cursos = Curso::all();
        return view('livewire.create-inscripciones',['prospectos'=>$prospectos
                                                    ,'cursos'=>$cursos]);
    }
}
