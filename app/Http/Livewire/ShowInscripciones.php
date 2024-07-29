<?php

namespace App\Http\Livewire;

use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Prospecto;
use Livewire\Component;
use Livewire\WithPagination;

class ShowInscripciones extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'inscripciones_id';
    public $direction = 'asc';
    public $inscripcion;
    public $cant = 5;
    public $readyToLoad = false;

    public $open_edit = false;
    protected $listeners = ['render','delete'];

    protected $rules = [
        'inscripcion.prospectos_id'=>'required',
        'inscripcion.cursos_id'=>'required',
        'inscripcion.fecha_inscripcion'=>'required|date',
    ];

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){
            $inscripciones = Inscripcion::orderBy($this->sort,$this->direction)
                                        ->paginate($this->cant);
            // $prospectos = Prospecto::where('prospectos_nombres','like','%'.trim($this->search).'%')
            //                        ->orWhere('prospectos_apellidos','like','%'.trim($this->search).'%')
            //                        ->orderBy($this->sort,$this->direction)
            //                        ->paginate($this->cant);
        } else {
            $inscripciones = array();
        }

        $prospectos = Prospecto::all();
        $cursos = Curso::all();
        return view('livewire.show-inscripciones',['inscripciones'=>$inscripciones
                                                  , 'prospectos'=>$prospectos
                                                  , 'cursos'=>$cursos]);
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

    public function edit(Inscripcion $inscripcion){
        $this->inscripcion = $inscripcion;
        $this->open_edit = true;
    }

    public function update(){
        $this->inscripcion->save();
        $this->reset(['open_edit']);
        $this->emit('alert','La inscripción fue modificado satifactoriamente');

    }

    public function delete(Inscripcion $inscripcion){
        $inscripcion->delete();
        $this->emit('alert','La inscripción fue eliminado satifactoriamente');
    }

}
