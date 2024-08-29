<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Origen;
use App\Models\Seguimiento;
use App\Models\Estatu;
use App\Models\Prospecto;

class CreateProspect extends Component
{

    public $open = false;

    public $prospectos_nombres,$prospectos_apellidos,$prospectos_telefono;
    public $prospectos_correo,$origenes_id,$seguimientos_id,$estatus_id;
    public $prospectos_comentarios,$prospectos_fecha,$prospectos_clase_fecha;
    public $prospectos_clase_hora;

    protected $rules = [
        'prospectos_nombres'=>'required|min:3|max:50',
        'prospectos_apellidos'=>'required|min:3|max:50',
        'prospectos_telefono'=>'required|numeric',
        'prospectos_correo'=>'required|email|max:100',
        'origenes_id'=>'required',
        'seguimientos_id'=>'required',
        'estatus_id'=>'required',
        'prospectos_comentarios'=>'required|min:7|max:255',
        'prospectos_fecha'=>'required|date',
        'prospectos_clase_fecha'=>'date|required_if:seguimientos_id,2',
        'prospectos_clase_hora'=>'required_if:seguimientos_id,2',
    ];



    /* public function updated($propertyName){
        $this->validateOnly($propertyName);
    } */

    public function mount()
    {
        $this->prospectos_fecha = date('Y-m-d');
        $this->prospectos_clase_fecha = date('Y-m-d');
    }

    public function save(){
        $this->validate();
        Prospecto::create([
            'prospectos_nombres' =>$this->prospectos_nombres,
            'prospectos_apellidos' =>$this->prospectos_apellidos,
            'prospectos_telefono' =>$this->prospectos_telefono,
            'prospectos_correo' =>$this->prospectos_correo,
            'origenes_id' =>$this->origenes_id,
            'seguimientos_id' =>$this->seguimientos_id,
            'estatus_id' =>$this->estatus_id,
            'prospectos_comentarios' =>$this->prospectos_comentarios,
            'prospectos_fecha' =>$this->prospectos_fecha,
            'prospectos_clase_fecha' =>$this->prospectos_clase_fecha,
            'prospectos_clase_hora' =>$this->prospectos_clase_hora
        ]);
        $this->reset(['open','prospectos_nombres','prospectos_apellidos','prospectos_telefono',
        'prospectos_correo','origenes_id','seguimientos_id','estatus_id',
        'prospectos_comentarios','prospectos_fecha','prospectos_clase_fecha','prospectos_clase_hora']);
        $this->emitTo('show-prospectos','render');
        $this->emit('alert','El prospecto fue agregado satifactoriamente');
    }

    public function render()
    {
        $origenes = Origen::all();
        $seguimientos = Seguimiento::all();
        $estatus = Estatu::all();
        return view('livewire.create-prospect',['origenes'=>$origenes
                                               ,'seguimientos'=>$seguimientos
                                               ,'estatus'=>$estatus]);
    }
}
