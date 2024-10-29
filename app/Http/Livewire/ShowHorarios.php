<?php

namespace App\Http\Livewire;

use App\Models\Espacio;
use App\Models\Hora;
use App\Models\Horario;
use Livewire\Component;

class ShowHorarios extends Component
{

    public $fecha;

    public function boot()
    {
        $this->fecha = date('Y-m-d');
    }

    public function render()
    {
        $espacios = Espacio::all();
        $cant_espacios = Espacio::count();
        $horas = Hora::all();
        $horarios = Horario::whereDate('horarios_dia', $this->fecha)
        ->orderBy('espacios_id', 'asc')
        ->orderBy('horas_id', 'asc')
        ->get();
        dd($horarios);
        return view('livewire.show-horarios',[
                                            'espacios'=>$espacios
                                           ,'horas'=>$horas
                                           ,'cant_espacios'=>$cant_espacios
                                            ]);
    }
}
