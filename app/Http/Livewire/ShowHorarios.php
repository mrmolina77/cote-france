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
        $horas = Hora::all();
        $horarios = Horario::whereDate('horarios_dia', $this->fecha)
        ->orderBy('espacios_id', 'asc')
        ->orderBy('horas_id', 'asc')
        ->get();
        // dd($horarios);
        foreach ($horarios as $horario) {
            $array_horario[$horario->horarios_dia][$horario->horas_id][$horario->espacios_id] = "$horario->grupo";
        }
        return view('livewire.show-horarios',[
                                            'espacios'=>$espacios
                                           ,'horas'=>$horas
                                           ,'horarios'=>$array_horario
                                           ,'fecha'=>$this->fecha
                                            ]);
    }
}
