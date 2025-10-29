<?php

namespace App\Http\Livewire;

use App\Models\Modalidad;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Espacio;

class ShowEspacios extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'espacios_id';
    public $direction = 'asc';
    public $cant = 50;
    public $readyToLoad = false;

    public $open_edit = false;
    public $espacio;
    protected $listeners = ['render','delete'];

    protected $rules = [
        'espacio.espacios_nombre'=>'required|min:3|max:100',
        'espacio.espacios_descripcion'=>'required|min:3|max:100',
        'espacio.espacios_enlace'=>'required_if:espacio.modalidad_id,2',
        'espacio.espacios_activo'=>'required',
        'espacio.modalidad_id'=>'required',
    ];

    public function updatingSearch(){
        $this->resetPage();
    }



    public function render()
    {
        if($this->readyToLoad){
            // $clasespruebas = ClasePrueba::orderBy($this->sort,$this->direction)
            //                             ->paginate($this->cant);
            $espacios = DB::table('espacios')
            ->select('espacios.espacios_nombre','espacios.espacios_descripcion'
                    ,'espacios.espacios_enlace','espacios.espacios_id'
                    ,'espacios.espacios_activo','modalidades.modalidad_nombre')
            ->join('modalidades','espacios.modalidad_id','=','modalidades.modalidad_id')
            ->orWhere('espacios.espacios_nombre','like','%'.trim($this->search).'%')
            ->orWhere('espacios.espacios_descripcion','like','%'.trim($this->search).'%')
            ->orWhere('espacios.espacios_enlace','like','%'.trim($this->search).'%')
            ->paginate($this->cant);
        } else {
            $espacios = array();
        }
        $modalidades = Modalidad::all();

        return view('livewire.show-espacios',['espacios'=>$espacios,'modalidades'=>$modalidades]);
    }

    public function loadPosts(){
        $this->readyToLoad = true;
    }

    public function order($sort){
        if($this->sort == $sort){
            if($this->direction == 'desc'){
                $this->direction = 'asc';
            } else {
                $this->direction = 'desc';
            }
        } else {
            $this->sort = $sort;
            $this->direction = 'asc';
        }
    }

    public function edit($id){
        $this->open_edit = true;
        $this->espacio = Espacio::find($id);
    }

    public function update(){
        $this->validate();
        $this->espacio->save();
        $this->reset(['open_edit']);
        $this->emitTo('show-espacios','render');
        $this->emit('alert','El espacio fue actualizado satifactoriamente');
    }

    public function delete(Espacio $espacio){
        try {
            $espacio->delete();
            $this->emit('alert','El espacio fue eliminado satifactoriamente');
        } catch (\Exception $e) {
            $this->emit('alert','Error al eliminar el espacio ya esta siendo utilizado','warning');
        }
    }
}
