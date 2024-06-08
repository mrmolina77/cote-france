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
    public $prospectos_comentarios,$prospectos_fecha;

    public function save(){
        Prospecto::create([
            'prospectos_nombres' =>$this->prospectos_nombres,
            'prospectos_apellidos' =>$this->prospectos_apellidos,
            'prospectos_telefono' =>$this->prospectos_telefono,
            'prospectos_correo' =>$this->prospectos_correo,
            'origenes_id' =>$this->origenes_id,
            'seguimientos_id' =>$this->seguimientos_id,
            'estatus_id' =>$this->estatus_id,
            'prospectos_comentarios' =>$this->prospectos_comentarios,
            'prospectos_fecha' =>$this->prospectos_fecha
        ]);
        $this->reset(['open','prospectos_nombres','prospectos_apellidos','prospectos_telefono',
        'prospectos_correo','origenes_id','seguimientos_id','estatus_id',
        'prospectos_comentarios','prospectos_fecha']);
        $this->emit('render');
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
