<?php

namespace App\Http\Livewire;

use App\Models\Curso;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Prospecto;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CreateInscripciones extends Component
{
    public $open = false;

    public $fecha_inscripcion,$prospectos_id, $cursos_id, $grupo_id;

    protected $rules = [
        'prospectos_id'=>'required|integer|exists:prospectos,prospectos_id',
        'cursos_id'=>'required|integer|exists:cursos,cursos_id',
        'grupo_id'=>'required|integer|exists:grupos,grupo_id',
        'fecha_inscripcion'=>'required|date',
    ];

    public function boot()
    {
        $this->fecha_inscripcion = date('Y-m-d');
    }

    public function mount()
    {
        Gate::authorize('manage-inscripciones');
    }

    public function save(){
        Gate::authorize('manage-inscripciones');
        $this->validate();

        if (Inscripcion::where('prospectos_id', $this->prospectos_id)->exists()) {
            $this->addError('prospectos_id', 'El prospecto ya cuenta con una inscripción.');

            return;
        }

        Inscripcion::create([
            'prospectos_id' =>$this->prospectos_id,
            'cursos_id' =>$this->cursos_id,
            'grupo_id' =>$this->grupo_id,
            'fecha_inscripcion' =>$this->fecha_inscripcion
        ]);
        $this->reset(['open','prospectos_id','cursos_id','grupo_id']);
        $this->emitTo('show-inscripciones','render');
        $this->emit('alert','La inscripción fue agregado satifactoriamente');
    }

    public function render()
    {
        $prospectos = Prospecto::whereNotIn('prospectos_id', function($query) {
            $query->select('prospectos_id')->from('inscripciones');
        })->get();
        $cursos = Curso::all();
        $grupos = Grupo::all();
        return view('livewire.create-inscripciones',['prospectos'=>$prospectos
                                                    ,'grupos'=>$grupos
                                                    ,'cursos'=>$cursos]);
    }
}
