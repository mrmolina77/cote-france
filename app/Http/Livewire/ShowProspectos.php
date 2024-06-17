<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Prospecto;
use App\Models\Origen;
use App\Models\Seguimiento;
use App\Models\Estatu;
use Livewire\WithPagination;

class ShowProspectos extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'prospectos_id';
    public $direction = 'asc';
    public $prospecto;
    public $cant = 5;

    public $open_edit = false;
    protected $listeners = ['render','delete' ];



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

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        $prospectos = Prospecto::where('prospectos_nombres','like','%'.trim($this->search).'%')
                               ->orWhere('prospectos_apellidos','like','%'.trim($this->search).'%')
                               ->orderBy($this->sort,$this->direction)
                               ->paginate($this->cant);
        $origenes = Origen::all();
        $seguimientos = Seguimiento::all();
        $estatus = Estatu::all();
        return view('livewire.show-prospetos',['prospectos'=>$prospectos
                                              ,'origenes'=>$origenes
                                              ,'seguimientos'=>$seguimientos
                                              ,'estatus'=>$estatus]);
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

    public function edit(Prospecto $prospecto){
        $this->prospecto = $prospecto;
        $this->open_edit = true;
    }

    public function update(){
        $this->prospecto->save();
        $this->reset(['open_edit']);
        $this->emit('alert','El prospecto fue modificado satifactoriamente');

    }

    public function delete(Prospecto $prospecto){
        $prospecto->delete();
        $this->emit('alert','El prospecto fue eliminado satifactoriamente');
    }
}
