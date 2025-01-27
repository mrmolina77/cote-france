<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Origen;
use App\Models\Seguimiento;
use App\Models\Estatu;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Modalidad;
use App\Models\Profesor;
use App\Models\Prospecto;
use App\Models\User;
use App\Notifications\ClassCreated;

class CreateProspect extends Component
{

    public $open = false;

    public $prospectos_nombres,$prospectos_apellidos,$prospectos_telefono1,$prospectos_telefono2;
    public $prospectos_correo,$origenes_id,$seguimientos_id,$estatus_id,$grupoid,$horarios_id;
    public $prospectos_comentarios,$prospectos_fecha,$prospectos_clase_fecha;
    public $prospectos_clase_hora,$horarios,$modalidad_id;

    protected $rules = [
        'prospectos_nombres'=>'required|min:3|max:50',
        'prospectos_apellidos'=>'required|min:3|max:50',
        'prospectos_telefono1'=>'required|numeric',
        'prospectos_telefono2'=>'nullable|numeric',
        'prospectos_correo'=>'required|email|max:100',
        'origenes_id'=>'required',
        'seguimientos_id'=>'required',
        'estatus_id'=>'required',
        'modalidad_id'=>'required',
        'grupoid'=>'required_if:seguimientos_id,2',
        'horarios_id'=>'required_if:seguimientos_id,2',
        'prospectos_comentarios'=>'required|min:7|max:255',
        'prospectos_fecha'=>'required|date',
    ];



    /* public function updated($propertyName){
        $this->validateOnly($propertyName);
    } */

    public function boot()
    {
        $this->prospectos_fecha = date('Y-m-d');
        $this->horarios = collect([]);
    }

    public function save(){
        $this->validate();
        $prospecto = Prospecto::create([
            'prospectos_nombres' =>$this->prospectos_nombres,
            'prospectos_apellidos' =>$this->prospectos_apellidos,
            'prospectos_telefono1' =>$this->prospectos_telefono1,
            'prospectos_telefono2' =>$this->prospectos_telefono2,
            'prospectos_correo' =>$this->prospectos_correo,
            'origenes_id' =>$this->origenes_id,
            'seguimientos_id' =>$this->seguimientos_id,
            'estatus_id' =>$this->estatus_id,
            'modalidad_id' =>$this->modalidad_id,
            'grupo_id' =>$this->grupoid,
            'horarios_id' =>$this->horarios_id,
            'prospectos_comentarios' =>$this->prospectos_comentarios,
            'prospectos_fecha' =>$this->prospectos_fecha
        ]);
        if ($this->seguimientos_id == 2) {
            $grupo = Grupo::find($this->grupoid);
            $horario = Horario::find($this->horarios_id);
            $profesor = Profesor::find($horario->profesores_id);

            $datos = ['nombre'=>$this->prospectos_nombres.' '.$this->prospectos_apellidos,
                      'hora'=>$horario->hora->horas_desde,
                      'prospectos_telefono'=>$this->prospectos_telefono1,
                      'fecha'=>$horario->horarios_dia,
                      'prospectos_correo'=>$this->prospectos_correo];
            $profesor->notify(new ClassCreated($datos));
        }
        $this->reset(['open','prospectos_nombres','prospectos_apellidos','prospectos_telefono1','prospectos_telefono2',
        'prospectos_correo','origenes_id','seguimientos_id','estatus_id','grupoid','horarios_id',
        'prospectos_comentarios','prospectos_clase_fecha','prospectos_clase_hora','modalidad_id']);
        $this->emitTo('show-prospectos','render');
        $this->emit('alert','El prospecto fue agregado satifactoriamente');
    }

    public function render()
    {
        $origenes = Origen::all();
        $seguimientos = Seguimiento::all();
        $estatus = Estatu::all();
        $grupos = Grupo::all();
        $modalidades = Modalidad::all();
        return view('livewire.create-prospect',['origenes'=>$origenes
                                               ,'seguimientos'=>$seguimientos
                                               ,'grupos'=>$grupos
                                               ,'modalidades'=>$modalidades
                                               ,'estatus'=>$estatus]);
    }

    public function updatedGrupoid($grupo_id){
        $this->horarios =  Horario::where('grupo_id',$grupo_id)->where('horarios_dia','>=',date('Y-m-d'))->get();
    }
}
