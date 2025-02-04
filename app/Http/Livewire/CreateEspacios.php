<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Espacio;
use App\Models\Modalidad;

class CreateEspacios extends Component
{
    public $open = false;
    public $espacios_nombre,$espacios_descripcion,$espacios_enlace,$espacios_activo,$modalidad_id;

    protected $rules = [
        'espacios_nombre'=>'required',
        'espacios_descripcion'=>'required',
        'espacios_enlace'=>'required_if:modalidad_id,2',
        'espacios_activo'=>'required',
        'modalidad_id'=>'required',
    ];

    public function save(){
        $this->validate();
        Espacio::create([
            'espacios_nombre' =>$this->espacios_nombre,
            'espacios_descripcion' =>$this->espacios_descripcion,
            'espacios_enlace' =>$this->espacios_enlace,
            'espacios_activo' =>$this->espacios_activo,
            'modalidad_id' =>$this->modalidad_id
        ]);
        $this->reset(['open','espacios_nombre','espacios_descripcion','espacios_enlace','espacios_activo','modalidad_id']);
        $this->emitTo('show-espacios','render');
        $this->emit('alert','El espacio fue agregado satifactoriamente');
    }

    public function boot()
    {
        $this->espacios_activo = false;
    }



    public function render()
    {
        $modalidades = Modalidad::all();
        return view('livewire.create-espacios',['modalidades'=>$modalidades]);
    }
}
