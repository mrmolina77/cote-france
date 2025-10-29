<?php

namespace App\Http\Livewire;

use App\Models\Asistencia;
use Livewire\Component;
use App\Models\Prospecto;
use App\Notifications\ClassReminder;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class ShowProgramadas extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'horarios_dia';
    public $direction = 'asc';
    public $prospecto;
    public $cant = 50;
    public $readyToLoad = false;
    public $open_edit = false;
    public $asistencia;

    public $prospectos_id,$asistencias,$asistencias_fecha;

    public function mount(){
        $this->readyToLoad = true;
        $this->asistencias_fecha = date('Y-m-d');
        $this->asistencias = 2;
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
                        ->select('prospectos.prospectos_id','prospectos_nombres','prospectos_apellidos','prospectos_telefono1','prospectos_correo'
                        ,'origenes_descripcion','estatus_descripcion','asistencias','horas_desde','horarios_dia')
                        ->leftJoin('origenes','prospectos.origenes_id','=','origenes.origenes_id')
                        ->leftJoin('estatus','prospectos.estatus_id','=','estatus.estatus_id')
                        ->leftJoin('grupos','grupos.grupo_id','=','prospectos.grupo_id')
                        ->leftJoin('horarios','horarios.horarios_id','=','prospectos.horarios_id')
                        ->leftJoin('profesores','profesores.profesores_id','=','horarios.profesores_id')
                        ->leftJoin('horas','horas.horas_id','=','horarios.horas_id')
                        ->leftJoin('asistencias', 'prospectos.prospectos_id', '=', 'asistencias.prospectos_id')
                        ->whereNotNull('grupos.grupo_id')
                        ->where('prospectos.seguimientos_id',2)
                        ->where(function ($query) {
                            $query->orWhere('prospectos.prospectos_nombres','like','%'.trim($this->search).'%')
                                  ->orWhere('prospectos.prospectos_apellidos','like','%'.trim($this->search).'%')
                                  ->orWhere(DB::raw('DATE_FORMAT(horarios.horarios_dia,"%d-%m-%Y")'),'like','%'.trim($this->search).'%')
                                  ->orWhere('horas.horas_desde','like','%'.trim($this->search).'%')
                                  ->orWhere('origenes.origenes_descripcion','like','%'.trim($this->search).'%');
                        })
                        ->where(function ($query) {
                            $query->whereNull('asistencias')
                                  ->orWhere('asistencias','2');
                        })
                        ->orderBy($this->sort,$this->direction)
                        ->paginate($this->cant);
        } else {
            $prospectos = array();
        }

        $sel_asistencias[2] = 'En espera';
        $sel_asistencias[0] = 'No asistio';
        $sel_asistencias[1] = 'Asistio';

        return view('livewire.show-programadas',['prospectos'=>$prospectos
                                              ,'sel_asistencias'=>$sel_asistencias]);
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
        $this->asistencia = Asistencia::where('prospectos_id',$id)->first();
        $this->prospecto = $prospecto;
        if($this->asistencia){
            $this->prospectos_id = $this->asistencia->prospectos_id;
            $this->asistencias = $this->asistencia->asistencias;
            $this->asistencias_fecha = $this->asistencia->asistencias_fecha ;
        } else {
            $this->prospectos_id = $id;
            $this->asistencias = 2;
            $this->asistencias_fecha = date('Y-m-d');
        }
        $this->open_edit = true;
    }

    public function update(){
        if($this->asistencia){
            $this->asistencia->prospectos_id = $this->prospectos_id;
            $this->asistencia->asistencias = $this->asistencias;
            $this->asistencia->asistencias_fecha = $this->asistencias_fecha;
            $this->asistencia->save();
        } else {
            $asistencia = Asistencia::create([
                'prospectos_id' => $this->prospectos_id,
                'asistencias' => $this->asistencias,
                'asistencias_fecha' => $this->asistencias_fecha,
            ]);
        }
        $this->reset(['open_edit','prospectos_id','asistencias','asistencias_fecha']);
        $this->emit('alert','La asistencia fue actualización satisfactoriamente');

    }

    public function notification($id){
        $prospecto = Prospecto::find($id);
        $prospecto->notify(new ClassReminder($prospecto));
        $this->emit('alert','La notificación fue enviada satisfactoriamente');
    }

}

