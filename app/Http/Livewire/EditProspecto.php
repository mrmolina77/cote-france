<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Prospecto;
use App\Models\Origen;
use App\Models\Seguimiento;
use App\Models\Estatu;

class EditProspecto extends Component
{
    public $prospecto;

    public $open = false;

    protected $rules = [
        'prospecto.prospectos_nombres'=>'required|min:3|max:50',
        'prospecto.prospectos_apellidos'=>'required|min:3|max:50',
        'prospecto.prospectos_telefono'=>'required|numeric',
        'prospecto.prospectos_correo'=>'required|email|max:100',
        'prospecto.origenes_id'=>'required',
        'prospecto.seguimientos_id'=>'required',
        'prospecto.estatus_id'=>'required',
        'prospecto.prospectos_comentarios'=>'required|min:7|max:255',
        'prospecto.prospectos_fecha'=>'required|date',
    ];


    public function mount(Prospecto $item){
        $this->prospecto = $item;
    }

    public function render()
    {
        $origenes = Origen::all();
        $seguimientos = Seguimiento::all();
        $estatus = Estatu::all();
        return view('livewire.edit-prospecto',['origenes'=>$origenes
                                            ,'seguimientos'=>$seguimientos
                                            ,'estatus'=>$estatus]);
    }
}
