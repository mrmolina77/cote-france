<?php

namespace App\Http\Livewire;

use App\Models\ClasePrueba;
use App\Models\Profesor;
use Livewire\Component;

class CreateClasesPruebas extends Component
{
    public $open = false;

    public $clasespruebas_fecha,$clasespruebas_descripcion, $clasespruebas_hora_inicio;
    public $clasespruebas_hora_fin, $profesores_id;

    protected $rules = [
        'clasespruebas_fecha'=>'required|date',
        'clasespruebas_descripcion'=>'required|min:3|max:50',
        'clasespruebas_hora_inicio'=>'required',
        'clasespruebas_hora_fin'=>'required',
        'profesores_id'=>'required',
    ];

    public function mount()
    {
        $this->clasespruebas_fecha = date('Y-m-d');
    }

    public function save(){
        $this->validate();
        ClasePrueba::create([
            'clasespruebas_fecha' =>$this->clasespruebas_fecha,
            'clasespruebas_descripcion' =>$this->clasespruebas_descripcion,
            'clasespruebas_hora_inicio' =>$this->clasespruebas_hora_inicio,
            'clasespruebas_hora_fin' =>$this->clasespruebas_hora_fin,
            'profesores_id' =>$this->profesores_id
        ]);
        $this->reset(['open','clasespruebas_fecha','clasespruebas_descripcion','clasespruebas_hora_inicio','clasespruebas_hora_fin',
        'profesores_id']);
        $this->emitTo('show-clases-pruebas','render');
        $this->emit('alert','La clase de prueba fue agregado satifactoriamente');
    }

    public function render()
    {
        $profesores = Profesor::all();
        return view('livewire.create-clases-pruebas',['profesores'=>$profesores]);
    }
}
