<?php

namespace App\Http\Livewire;

use App\Models\Curso;
use Livewire\WithPagination;
use Livewire\Component;
use App\Models\Prospecto;
use App\Models\Origen;
use App\Models\Seguimiento;
use App\Models\Estatu;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Modalidad;
use Illuminate\Support\Facades\DB;

class ShowProspectos extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'prospectos_id';
    public $direction = 'asc';
    public $prospecto;
    public $cant = 50;
    public $readyToLoad = false;
    public $horarios,$grupoid;

    public $open_edit = false;
    protected $listeners = ['render','delete'];

    protected $rules = [
        'prospecto.prospectos_nombres'=>'required|min:3|max:50',
        'prospecto.prospectos_apellidos'=>'nullable|min:3|max:50',
        'prospecto.prospectos_telefono1'=>'required|numeric',
        'prospecto.prospectos_telefono2'=>'nullable|numeric ',
        'prospecto.prospectos_correo'=>'nullable|email|max:100',
        'prospecto.origenes_id'=>'nullable',
        'prospecto.seguimientos_id'=>'nullable',
        'prospecto.estatus_id'=>'nullable',
        'prospecto.modalidad_id'=>'nullable',
        'prospecto.cursos_id'=>'nullable',
        'prospecto.prospectos_comentarios'=>'nullable|min:7|max:255',
        'prospecto.prospectos_fecha'=>'required|date',
    ];

    public function boot()
    {
        $this->horarios = collect([]);
    }

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){
            // $prospectos = Prospecto::where('prospectos_nombres','like','%'.trim($this->search).'%')
            //                        ->orWhere('prospectos_apellidos','like','%'.trim($this->search).'%')
            //                        ->where('origenes_descripcion','like', '%'.trim($this->search).'%')
            //                        ->where('estatus_descripcion','like', '%'.trim($this->search).'%')
            //                        ->orderBy($this->sort,$this->direction)
            //                        ->paginate($this->cant);

            $prospectos = DB::table('prospectos')
                        ->select('prospectos_id','prospectos_nombres','prospectos_apellidos','prospectos_telefono1','prospectos_telefono2','origenes_descripcion','estatus_descripcion')
                        ->leftJoin('origenes','prospectos.origenes_id','=','origenes.origenes_id')
                        ->leftJoin('estatus','prospectos.estatus_id','=','estatus.estatus_id')
                        ->orWhere('prospectos.prospectos_nombres','like','%'.trim($this->search).'%')
                        ->orWhere('prospectos.prospectos_apellidos','like','%'.trim($this->search).'%')
                        ->orWhere('origenes.origenes_descripcion','like','%'.trim($this->search).'%')
                        ->orWhere('estatus.estatus_descripcion','like','%'.trim($this->search).'%')
                        ->orWhere('prospectos.prospectos_telefono1','like','%'.trim($this->search).'%')
                        ->orderBy($this->sort,$this->direction)
                        ->paginate($this->cant);
        } else {
            $prospectos = array();
        }
        $origenes = Origen::all();
        $seguimientos = Seguimiento::all();
        $estatus = Estatu::all();
        $grupos = Grupo::all();
        $modalidades = Modalidad::all();
        $cursos = Curso::all();
        return view('livewire.show-prospetos',['prospectos'=>$prospectos
                                              ,'origenes'=>$origenes
                                              ,'grupos'=>$grupos
                                              ,'modalidades'=>$modalidades
                                              ,'seguimientos'=>$seguimientos
                                              ,'cursos'=>$cursos
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
        $prospecto = Prospecto::find($id);
        $this->horarios = Horario::where('grupo_id',$prospecto->grupo_id)->get();
        $this->grupoid = $prospecto->grupo_id;
        $this->prospecto = $prospecto;
        $this->open_edit = true;
    }

    public function update(){
        $this->prospecto->save();
        $this->reset(['open_edit']);
        $this->emit('alert','El prospecto fue modificado satifactoriamente');

    }

    public function delete(Prospecto $prospecto){
        try {
            $prospecto->delete();
            $this->emit('alert','El prospecto fue eliminado satifactoriamente');
        } catch (\Exception $e) {
            $this->emit('alert','No se pudo eliminar el prospecto, tiene información relacionada','Error!', 'error');
            return;
        }
    }

    public function updatedGrupoid($grupo_id){
        $this->prospecto->grupo_id = $grupo_id;
        if(isset($grupo_id))
        $this->horarios =  Horario::where('grupo_id',$grupo_id)->get();
    }
}
