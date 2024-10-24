<?php

namespace App\Http\Livewire;

use App\Models\Grupo;
use App\Models\Estado;
use App\Models\Modalidad;
use App\Models\Profesor;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ShowGrupos extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'grupo_id';
    public $direction = 'asc';
    public $grupo;
    public $cant = 5;
    public $readyToLoad = false;

    public $open_edit = false;
    protected $listeners = ['render','delete'];

    protected $rules = [
        'grupo.grupo_nombre'=>'required|min:3|max:50',
        'grupo.grupo_nivel'=>'required|min:3|max:50',
        'grupo.grupo_capitulo'=>'required|numeric',
        'grupo.grupo_libro_maestro'=>'required|min:7|max:255',
        'grupo.grupo_libro_alumno'=>'required|min:7|max:255',
        'grupo.grupo_observacion'=>'required|min:7|max:255',
        'grupo.modalidad_id'=>'required',
        'grupo.profesores_id'=>'required',
        'grupo.estado_id'=>'required',
    ];

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){


            $grupos = DB::table('grupos')
                        ->select('grupo_id','grupo_nombre','grupo_nivel','grupo_capitulo',
                                 'grupo_libro_maestro','grupo_libro_alumno','grupo_observacion','modalidad_nombre','estado_nombre')
                        ->join('modalidades','grupos.modalidad_id','=','modalidades.modalidad_id')
                        ->join('estados','grupos.estado_id','=','estados.estado_id')
                        ->orWhere('grupos.grupo_nombre','like','%'.trim($this->search).'%')
                        ->orWhere('grupos.grupo_nivel','like','%'.trim($this->search).'%')
                        ->orWhere('grupos.grupo_capitulo','like','%'.trim($this->search).'%')
                        ->orWhere('modalidades.modalidad_nombre','like','%'.trim($this->search).'%')
                        ->orWhere('estados.estado_nombre','like','%'.trim($this->search).'%')
                        ->orderBy($this->sort,$this->direction)
                        ->paginate($this->cant);
        } else {
            $grupos = array();
        }
        $modalidades = Modalidad::all();
        $estados = Estado::all();
        $profesores = Profesor::all();
        return view('livewire.show-grupos',['grupos'=>$grupos
                                           ,'modalidades'=>$modalidades
                                           ,'profesores'=>$profesores
                                           ,'estados'=>$estados]);
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
        $grupo = Grupo::find($id);
        $this->grupo = $grupo;
        $this->open_edit = true;
    }

    public function update(){
        $this->grupo->save();
        $this->reset(['open_edit']);
        $this->emit('alert','El grupo fue modificado satifactoriamente');

    }

    public function delete(Grupo $grupo){
        $grupo->delete();
        $this->emit('alert','El grupo fue eliminado satifactoriamente');
    }
}
