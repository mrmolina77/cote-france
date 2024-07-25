<?php

namespace App\Http\Livewire;

use App\Models\Asistencia;
use App\Models\ClasePrueba;
use App\Models\Prospecto;
use Livewire\Component;

class CreateAsistencias extends Component
{
    public $open = false;

    public $prospectos_id,$clasespruebas_id, $asistencias;
    public $asistencias_fecha;

    protected $rules = [
        'prospectos_id'=>'required',
        'clasespruebas_id'=>'required',
        'asistencias'=>'required|boolean',
        'asistencias_fecha'=>'required|date',
    ];

    public function save(){
        $this->validate();
        Asistencia::create([
            'prospectos_id' =>$this->prospectos_id,
            'clasespruebas_id' =>$this->clasespruebas_id,
            'asistencias' =>$this->asistencias,
            'asistencias_fecha' =>$this->asistencias_fecha
        ]);
        $this->reset(['open','prospectos_id','clasespruebas_id','asistencias','asistencias_fecha']);
        $this->emitTo('show-asistencias','render');
        $this->emit('alert','La asistencia fue agregado satifactoriamente');
    }

    public function render()
    {
        $prospectos = Prospecto::all();
        $clasespruebas = ClasePrueba::all();
        return view('livewire.create-asistencias',['prospectos'=>$prospectos
                                                  ,'clasespruebas'=>$clasespruebas]);
    }
}
