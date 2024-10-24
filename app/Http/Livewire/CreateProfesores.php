<?php

namespace App\Http\Livewire;

use App\Models\Profesor;
use Livewire\Component;

class CreateProfesores extends Component
{
    public $open = false;

    public $profesores_nombres,$profesores_apellidos, $profesores_email;
    public $profesores_fecha_ingreso, $profesores_precio_hora, $profesores_horas_mes;

    protected $rules = [
        'profesores_nombres'=>'required|min:3|max:100',
        'profesores_apellidos'=>'required|min:3|max:100',
        'profesores_email'=>'required|email',
        'profesores_fecha_ingreso'=>'required|date',
        'profesores_precio_hora'=>'decimal:2',
        'profesores_horas_mes'=>'integer',
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
            'profesores_fecha_ingreso' =>$this->profesores_fecha_ingreso,
            'profesores_precio_hora' =>$this->profesores_precio_hora,
            'profesores_horas_mes' =>$this->profesores_horas_mes
        ]);
        $this->reset(['open','profesores_nombres','profesores_apellidos','profesores_email','profesores_fecha_ingreso',
        'profesores_precio_hora','profesores_horas_mes']);
        $this->emitTo('show-profesores','render');
        $this->emit('alert','El profesor fue agregado satifactoriamente');
    }

    public function render()
    {
        return view('livewire.create-profesores');
    }
}
