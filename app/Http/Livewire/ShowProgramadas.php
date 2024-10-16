<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Prospecto;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class ShowProgramadas extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'prospectos_clase_fecha';
    public $direction = 'asc';
    public $prospecto;
    public $cant = 5;
    public $readyToLoad = false;



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
                        ->select('prospectos.prospectos_id','prospectos_nombres','prospectos_apellidos','prospectos_telefono','prospectos_correo'
                        ,'origenes_descripcion','estatus_descripcion','prospectos_clase_fecha','prospectos_clase_hora','asistencias')
                        ->join('origenes','prospectos.origenes_id','=','origenes.origenes_id')
                        ->join('estatus','prospectos.estatus_id','=','estatus.estatus_id')
                        ->leftJoin('asistencias', 'prospectos.prospectos_id', '=', 'asistencias.prospectos_id')
                        ->whereNotNull('prospectos_clase_fecha')
                        ->where(function ($query) {
                            $query->orWhere('prospectos.prospectos_nombres','like','%'.trim($this->search).'%')
                                  ->orWhere('prospectos.prospectos_apellidos','like','%'.trim($this->search).'%')
                                  ->orWhere(DB::raw('DATE_FORMAT(prospectos.prospectos_clase_fecha,"%d-%m-%Y")'),'like','%'.trim($this->search).'%')
                                  ->orWhere('prospectos.prospectos_clase_hora','like','%'.trim($this->search).'%')
                                  ->orWhere('origenes.origenes_descripcion','like','%'.trim($this->search).'%');
                        })
                        ->where(function ($query) {
                            $query->whereNull('asistencias')
                                  ->orWhere('asistencias','0');
                        })
                        ->orderBy($this->sort,$this->direction)
                        ->paginate($this->cant);
        } else {
            $prospectos = array();
        }

        return view('livewire.show-programadas',['prospectos'=>$prospectos]);
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

}

