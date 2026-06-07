<?php

namespace App\Http\Livewire;

use App\Models\ClasePrueba;
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
    public $clase_prueba_id;
    public $asistencias, $asistencias_fecha;

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

            $prospectos = DB::table('clases_prueba')
                        ->select(
                            'clases_prueba.clase_prueba_id',
                            'clases_prueba.prospectos_id',
                            'clases_prueba.horarios_dia',
                            'clases_prueba.asistio as asistencias',
                            'prospectos.prospectos_nombres',
                            'prospectos.prospectos_apellidos',
                            'prospectos.prospectos_telefono1',
                            'prospectos.prospectos_correo',
                            'horas.horas_desde',
                            'origenes.origenes_descripcion'
                        )
                        ->join('prospectos','clases_prueba.prospectos_id','=','prospectos.prospectos_id')
                        ->leftJoin('horas','clases_prueba.horas_id','=','horas.horas_id')
                        ->leftJoin('origenes','prospectos.origenes_id','=','origenes.origenes_id')
                        ->whereNull('clases_prueba.deleted_at')
                        ->where(function ($query) {
                            $query->orWhere('prospectos.prospectos_nombres','like','%'.trim($this->search).'%')
                                  ->orWhere('prospectos.prospectos_apellidos','like','%'.trim($this->search).'%')
                                  ->orWhere(DB::raw('DATE_FORMAT(clases_prueba.horarios_dia,"%d-%m-%Y")'),'like','%'.trim($this->search).'%')
                                  ->orWhere('horas.horas_desde','like','%'.trim($this->search).'%')
                                  ->orWhere('origenes.origenes_descripcion','like','%'.trim($this->search).'%');
                        })
                        ->whereNull('clases_prueba.asistio')
                        ->orderBy($this->sort,$this->direction)
                        ->paginate($this->cant);
        } else {
            $prospectos = array();
        }

        $sel_asistencias[''] = 'En espera';
        $sel_asistencias[0] = 'No asistió';
        $sel_asistencias[1] = 'Asistió';

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
        $clase = ClasePrueba::find($id);
        if($clase) {
            $this->prospecto = $clase->prospecto;
            $this->clase_prueba_id = $id;
            $this->asistencias = is_null($clase->asistio) ? '' : $clase->asistio;
            $this->asistencias_fecha = $clase->horarios_dia ? $clase->horarios_dia->format('Y-m-d') : date('Y-m-d');
        }
        $this->open_edit = true;
    }

    public function update(){
        $clase = ClasePrueba::find($this->clase_prueba_id);
        if($clase){
            $asistio_val = $this->asistencias === '' ? null : $this->asistencias;
            $clase->asistio = $asistio_val;
            $clase->estado = is_null($asistio_val) ? 'programada' : ((int) $asistio_val === 1 ? 'asistio' : 'falto');
            if ($this->asistencias_fecha) {
                $clase->horarios_dia = $this->asistencias_fecha;
            }
            $clase->save();
        }
        $this->reset(['open_edit','clase_prueba_id','asistencias','asistencias_fecha']);
        $this->emit('alert','La asistencia fue actualización satisfactoriamente');

    }

    public function notification($id){
        $clase = ClasePrueba::find($id);
        if ($clase) {
            // Resolve relationships from horario/grupo if they are null on the ClasePrueba itself
            if (!$clase->profesor && $clase->horario) {
                $clase->setRelation('profesor', $clase->horario->profesor);
            }
            if (!$clase->espacio && $clase->horario) {
                $clase->setRelation('espacio', $clase->horario->espacio);
            }
            if (!$clase->hora && $clase->horario) {
                $clase->setRelation('hora', $clase->horario->hora);
            }
            if (!$clase->modalidad) {
                if ($clase->grupo) {
                    $clase->setRelation('modalidad', $clase->grupo->modalidad);
                } elseif ($clase->horario && $clase->horario->grupo) {
                    $clase->setRelation('modalidad', $clase->horario->grupo->modalidad);
                }
            }

            $prospecto = $clase->prospecto;
            if ($prospecto && $prospecto->prospectos_correo) {
                $prospecto->notify(new ClassReminder($clase));
            }

            $profesor = $clase->profesor;
            if ($profesor && $profesor->profesores_email) {
                $profesor->notify(new \App\Notifications\TeacherClassReminder($clase));
            }

            $this->emit('alert','La notificación fue enviada satisfactoriamente');
        } else {
            $this->emit('alert','Error: Clase de prueba no encontrada','Error!','error');
        }
    }

}

