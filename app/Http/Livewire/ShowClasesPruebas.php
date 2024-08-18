<?php

namespace App\Http\Livewire;

use App\Models\ClasePrueba;
use App\Models\Profesor;
use Illuminate\Support\Facades\DB;
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
        'claseprueba.clasespruebas_fecha'=>'required|date',
        'claseprueba.clasespruebas_descripcion'=>'required|min:3|max:50',
        'claseprueba.clasespruebas_hora_inicio'=>'required',
        'claseprueba.clasespruebas_hora_fin'=>'required',
        'claseprueba.profesores_id'=>'required',
    ];

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){
            // $clasespruebas = ClasePrueba::orderBy($this->sort,$this->direction)
            //                             ->paginate($this->cant);
            $clasespruebas = DB::table('clases_pruebas')
            ->join('profesores','profesores.profesores_id','=','clases_pruebas.profesores_id')
            ->orWhere('profesores.profesores_nombres','like','%'.trim($this->search).'%')
            ->orWhere('profesores.profesores_apellidos','like','%'.trim($this->search).'%')
            ->orWhere('clases_pruebas.clasespruebas_descripcion','like','%'.trim($this->search).'%')
            ->orWhere('clases_pruebas.clasespruebas_fecha','like','%'.trim($this->search).'%')
            ->orWhere('clases_pruebas.clasespruebas_hora_inicio','like','%'.trim($this->search).'%')
            ->orWhere('clases_pruebas.clasespruebas_hora_fin','like','%'.trim($this->search).'%')
            ->select('clases_pruebas.clasespruebas_descripcion','clases_pruebas.clasespruebas_fecha'
            ,'clases_pruebas.clasespruebas_hora_inicio','clases_pruebas.clasespruebas_hora_fin'
            ,'profesores.profesores_nombres','profesores.profesores_apellidos'
            ,'clases_pruebas.clasespruebas_id')
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

    public function edit($id){
        $claseprueba = ClasePrueba::find($id);
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
