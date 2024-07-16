<?php

namespace App\Http\Livewire;

use App\Models\ClasePrueba;
use App\Models\Profesor;
use Livewire\Component;
use Livewire\WithPagination;

class ShowClasesPruebas extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'clasespruebas_id';
    public $direction = 'asc';
    public $claseprueba;
    public $cant = 5;
    public $readyToLoad = false;

    public $open_edit = false;
    protected $listeners = ['render','delete'];



    protected $rules = [
        'clasesprueba.clasespruebas_fecha'=>'required|date',
        'clasesprueba.clasespruebas_hora_inicio'=>'required',
        'clasesprueba.clasespruebas_hora_fin'=>'required',
        'clasesprueba.profesores_id'=>'required',
    ];

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){
            $clasespruebas = ClasePrueba::orderBy($this->sort,$this->direction)
                                        ->paginate($this->cant);
            // $prospectos = Prospecto::where('prospectos_nombres','like','%'.trim($this->search).'%')
            //                        ->orWhere('prospectos_apellidos','like','%'.trim($this->search).'%')
            //                        ->orderBy($this->sort,$this->direction)
            //                        ->paginate($this->cant);
        } else {
            $clasespruebas = array();
        }

        $profesores = Profesor::all();
        return view('livewire.show-clases-pruebas',['clasespruebas'=>$clasespruebas
                                                  , 'profesores'=>$profesores]);
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

    public function edit(ClasePrueba $claseprueba){
        $this->claseprueba = $claseprueba;
        $this->open_edit = true;
    }

    public function update(){
        $this->claseprueba->save();
        $this->reset(['open_edit']);
        $this->emit('alert','La clase fue modificado satifactoriamente');

    }

    public function delete(ClasePrueba $claseprueba){
        $claseprueba->delete();
        $this->emit('alert','La clase de pruebas fue eliminado satifactoriamente');
    }
}
