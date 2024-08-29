<?php

namespace App\Http\Livewire;

use App\Models\Asistencia;
use App\Models\ClasePrueba;
use App\Models\Prospecto;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ShowAsistencias extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'asistencias_id';
    public $direction = 'asc';
    public $asistencia;
    public $cant = 5;
    public $readyToLoad = false;

    public $open_edit = false;
    protected $listeners = ['render','delete'];

    protected $rules = [
        'asistencia.prospectos_id'=>'required',
        'asistencia.clasespruebas_id'=>'required',
        'asistencia.asistencias_fecha'=>'required|date',
        'asistencia.asistencias'=>'required|boolean',
    ];

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){
            // $asistencias = Asistencia::orderBy($this->sort,$this->direction)
            //                             ->paginate($this->cant);
            $asistencias = DB::table('asistencias')
              ->join('prospectos','prospectos.prospectos_id','=','asistencias.prospectos_id')
              ->join('clases_pruebas','asistencias.clasespruebas_id','=','clases_pruebas.clasespruebas_id')
              ->orWhere('prospectos.prospectos_nombres','like','%'.trim($this->search).'%')
              ->orWhere('prospectos.prospectos_apellidos','like','%'.trim($this->search).'%')
              ->orWhere('asistencias.asistencias_fecha','like','%'.trim($this->search).'%')
              ->orWhere('clases_pruebas.clasespruebas_descripcion','like','%'.trim($this->search).'%')
              ->orWhere(DB::raw('DATE_FORMAT(clases_pruebas.clasespruebas_fecha,"%d-%m-%Y")'),'like','%'.trim($this->search).'%')
              ->orWhere('clases_pruebas.clasespruebas_hora_inicio','like','%'.trim($this->search).'%')
              ->orWhere(DB::raw('if(asistencias.asistencias,"Si","No")'),'like','%'.trim($this->search).'%')
              ->select(DB::raw('if(asistencias.asistencias,"Si","No") as asistio'),'asistencias.asistencias_fecha'
                       ,'clases_pruebas.clasespruebas_descripcion','clases_pruebas.clasespruebas_fecha'
                       ,'clases_pruebas.clasespruebas_hora_inicio','prospectos.prospectos_nombres'
                       ,'prospectos.prospectos_apellidos','asistencias.asistencias_id')
                ->orderBy($this->sort,$this->direction)
                ->paginate($this->cant);
            // $prospectos = Prospecto::where('prospectos_nombres','like','%'.trim($this->search).'%')
            //                        ->orWhere('prospectos_apellidos','like','%'.trim($this->search).'%')
            //                        ->orderBy($this->sort,$this->direction)
            //                        ->paginate($this->cant);
        } else {
            $asistencias = array();
        }

        $prospectos = Prospecto::all();
        $clasespruebas = ClasePrueba::all();
        return view('livewire.show-asistencias',['asistencias'=>$asistencias
                                                  , 'prospectos'=>$prospectos
                                                  , 'clasespruebas'=>$clasespruebas]);
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
        $asistencia = Asistencia::find($id);
        $this->asistencia = $asistencia;
        $this->open_edit = true;
    }

    public function update(){
        $this->asistencia->save();
        $this->reset(['open_edit']);
        $this->emit('alert','La asistencia fue modificado satifactoriamente');

    }

    public function delete(Asistencia $asistencia){
        $asistencia->delete();
        $this->emit('alert','La asistencia fue eliminado satifactoriamente');
    }




}
