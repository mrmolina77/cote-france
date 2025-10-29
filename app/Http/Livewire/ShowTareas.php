<?php

namespace App\Http\Livewire;

use App\Models\EstatuTarea;
use App\Models\Prospecto;
use App\Models\Tarea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ShowTareas extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'tareas_id';
    public $direction = 'asc';
    public $tarea;
    public $cant = 50;
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
        $user = Auth::user();
        if($this->readyToLoad){
            // $tareas = Tarea::where('tareas_descripcion','like','%'.trim($this->search).'%')
            //                        ->orderBy($this->sort,$this->direction)
            //                        ->paginate($this->cant);
            if ($user->role->roles_codigo == 'admin') {
                # code...
                $tareas = DB::table('tareas')
                ->join('prospectos','prospectos.prospectos_id','=','tareas.prospectos_id')
                ->join('estatu_tareas','estatu_tareas.est_tareas_id','=','tareas.est_tareas_id')
                ->join('users','users.id','=','tareas.user_id')
                ->orWhere('prospectos.prospectos_nombres','like','%'.trim($this->search).'%')
                ->orWhere('prospectos.prospectos_apellidos','like','%'.trim($this->search).'%')
                ->orWhere('estatu_tareas.est_tareas_descripcion','like','%'.trim($this->search).'%')
                ->orWhere('tareas.tareas_descripcion','like','%'.trim($this->search).'%')
                ->orWhere('tareas.tareas_fecha','like','%'.trim($this->search).'%')
                ->orWhere('users.name','like','%'.trim($this->search).'%')
                ->select('tareas.tareas_id','tareas.tareas_fecha'
                ,'prospectos.prospectos_nombres','prospectos.prospectos_apellidos'
                ,'estatu_tareas.est_tareas_descripcion','tareas.tareas_descripcion'
                ,'users.name')
                ->orderBy($this->sort,$this->direction)
                ->paginate($this->cant);
            } else {
                $tareas = DB::table('tareas')
                ->join('prospectos','prospectos.prospectos_id','=','tareas.prospectos_id')
                ->join('estatu_tareas','estatu_tareas.est_tareas_id','=','tareas.est_tareas_id')
                ->where('user_id',Auth::id())
                ->where(function ($query) {
                    $query->orWhere('prospectos.prospectos_nombres','like','%'.trim($this->search).'%')
                    ->orWhere('prospectos.prospectos_apellidos','like','%'.trim($this->search).'%')
                    ->orWhere('estatu_tareas.est_tareas_descripcion','like','%'.trim($this->search).'%')
                    ->orWhere('tareas.tareas_descripcion','like','%'.trim($this->search).'%')
                    ->orWhere('tareas.tareas_fecha','like','%'.trim($this->search).'%');
                })
                ->select('tareas.tareas_id','tareas.tareas_fecha'
                ,'prospectos.prospectos_nombres','prospectos.prospectos_apellidos'
                ,'estatu_tareas.est_tareas_descripcion','tareas.tareas_descripcion')
                ->orderBy($this->sort,$this->direction)
                ->paginate($this->cant);

            }

        } else {
            $tareas = array();
        }
        $prospectos = Prospecto::all();
        $estatus = EstatuTarea::all();
        return view('livewire.show-tareas',['tareas'=>$tareas
                                           ,'prospectos'=>$prospectos
                                           ,'user'=>$user
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

    public function edit($id){
        $tarea = Tarea::find($id);
        $this->tarea = $tarea;
        $this->open_edit = true;
    }

    public function update(){
        $this->tarea->save();
        $this->reset(['open_edit']);
        $this->emit('alert','La tarea fue modificado satifactoriamente');

    }

    public function delete(Tarea $tarea){
        try {
            $tarea->delete();
            $this->emit('alert','La tarea fue eliminado satifactoriamente');
        } catch (\Exception $e) {
            $this->emit('alert','No se pudo eliminar la tarea, tiene información relacionada','Error!', 'error');
            return;
        }
    }
}
