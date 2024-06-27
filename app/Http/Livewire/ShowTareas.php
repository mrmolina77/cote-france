<?php

namespace App\Http\Livewire;

use App\Models\EstatuTarea;
use App\Models\Prospecto;
use App\Models\Tarea;
use Livewire\Component;
use Livewire\WithPagination;

class ShowTareas extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'tareas_id';
    public $direction = 'asc';
    public $tarea;
    public $cant = 5;
    public $readyToLoad = false;

    public $open_edit = false;
    protected $listeners = ['render','delete'];

    protected $rules = [
        'tarea.tareas_descripcion'=>'required|min:10|max:100',
        'tarea.tareas_fecha'=>'required|date',
        'tarea.tareas_comentario'=>'required|min:10|max:512',
        'tarea.prospectos_id'=>'required',
        'tarea.est_tareas_id'=>'required',
    ];

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){
            $tareas = Tarea::where('tareas_descripcion','like','%'.trim($this->search).'%')
                                   ->orderBy($this->sort,$this->direction)
                                   ->paginate($this->cant);
        } else {
            $tareas = array();
        }
        $prospectos = Prospecto::all();
        $estatus = EstatuTarea::all();
        return view('livewire.show-tareas',['tareas'=>$tareas
                                           ,'prospectos'=>$prospectos
                                           ,'estatus'=>$estatus]);
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

    public function edit(Tarea $tarea){
        $this->tarea = $tarea;
        $this->open_edit = true;
    }

    public function update(){
        $this->tarea->save();
        $this->reset(['open_edit']);
        $this->emit('alert','La tarea fue modificado satifactoriamente');

    }

    public function delete(Tarea $tarea){
        $tarea->delete();
        $this->emit('alert','La tarea fue eliminado satifactoriamente');
    }
}
