<?php

namespace App\Http\Livewire;

use App\Models\Modalidad;
use App\Models\Profesor;
use Livewire\Component;

class CreateProfesores extends Component
{
    public $open = false;

    public $profesores_nombres,$profesores_apellidos, $profesores_email, $profesores_color;
    public $profesores_fecha_ingreso, $profesores_horas_semanales, $modalidad_id;

    protected $rules = [
        'profesores_nombres'=>'required|min:3|max:100',
        'profesores_apellidos'=>'required|min:3|max:100',
        'profesores_email'=>'required|email',
        'profesores_fecha_ingreso'=>'required|date',
        'profesores_horas_semanales'=>'integer',
        'profesores_color'=>'required',
        'modalidad_id'=>'required',
    ];

    public function boot()
    {
        $this->profesores_fecha_ingreso = date('Y-m-d');
    }

    public function save(){
        $this->validate();
        Profesor::create([
            'profesores_nombres' =>$this->profesores_nombres,
            'profesores_apellidos' =>$this->profesores_apellidos,
            'profesores_email' =>$this->profesores_email,
            'profesores_color' =>$this->profesores_color,
            'modalidad_id' =>$this->modalidad_id,
            'profesores_fecha_ingreso' =>$this->profesores_fecha_ingreso,
            'profesores_horas_semanales' =>$this->profesores_horas_semanales
        ]);
        $this->reset(['open','profesores_nombres','profesores_apellidos','profesores_email','profesores_fecha_ingreso',
        'profesores_color','profesores_horas_semanales','modalidad_id']);
        $this->emitTo('show-profesores','render');
        $this->emit('alert','El profesor fue agregado satifactoriamente');
    }

    public function render()
    {
        $modalidades = Modalidad::all();
        return view('livewire.create-profesores',['modalidades'=>$modalidades]);
    }
}
