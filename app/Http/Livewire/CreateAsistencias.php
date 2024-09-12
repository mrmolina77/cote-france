<?php

namespace App\Http\Livewire;

use App\Models\Asistencia;
use App\Models\Prospecto;
use Livewire\Component;

class CreateAsistencias extends Component
{
    public $open = false;

    public $prospectos_id,$asistencias,$asistencias_fecha;

    protected $rules = [
        'prospectos_id'=>'required',
        'asistencias'=>'required|boolean',
        'asistencias_fecha'=>'required|date',
    ];

    public function mount()
    {
        $this->asistencias_fecha = date('Y-m-d');
        $this->asistencias = false;
    }
    public function save(){
        $this->validate();
        Asistencia::create([
            'prospectos_id' =>$this->prospectos_id,
            'asistencias' =>$this->asistencias,
            'asistencias_fecha' =>$this->asistencias_fecha
        ]);
        $this->reset(['open','prospectos_id','asistencias','asistencias_fecha']);
        $this->emitTo('show-asistencias','render');
        $this->emit('alert','La asistencia fue agregado satifactoriamente');
    }

    public function render()
    {
        $prospectos = Prospecto::whereNotNull('prospectos_clase_fecha')->doesntHave('asistencia')->get();
        return view('livewire.create-asistencias',['prospectos'=>$prospectos]);
    }
}
