<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Prospecto;

class ShowProspectos extends Component
{
    protected $listeners = ['render'=>'render' ];
    
    public function render()
    {
        $prospectos = Prospecto::all();
        return view('livewire.show-prospetos',['prospectos'=>$prospectos]);
    }
}
