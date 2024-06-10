<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Prospecto;

class ShowProspectos extends Component
{
    public $search = "";
    public $sort = 'prospectos_id';
    public $direction = 'asc';
    
    protected $listeners = ['render' ];
    
    public function render()
    {
        $prospectos = Prospecto::where('prospectos_nombres','like','%'.trim($this->search).'%')
                               ->orWhere('prospectos_apellidos','like','%'.trim($this->search).'%')
                               ->orderBy($this->sort,$this->direction)
                               ->get();
        return view('livewire.show-prospetos',['prospectos'=>$prospectos]);
    }
    public function order($order){
        if ($this->sort== $order) {
            if ($this->direction == 'desc') {
                $this->direction = 'asc';
            } else {
                $this->direction = 'desc';
            }
        } else {
            $this->sort= $order;
            $this->direction = 'asc';
        }
        

        

    }
}
