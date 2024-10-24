<?php

namespace App\Http\Livewire;

use App\Models\Profesor;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ShowProfesores extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'profesores_id';
    public $direction = 'asc';
    public $profesor;
    public $cant = 5;
    public $readyToLoad = false;

    public $open_edit = false;
    protected $listeners = ['render','delete'];



    protected $rules = [
        'profesores.profesores_nombres'=>'required|min:3|max:100',
        'profesores.profesores_apellidos'=>'required|min:3|max:100',
        'profesores.profesores_email'=>'required|email',
        'profesores.profesores_fecha_ingreso'=>'required|date',
        'profesores.profesores_precio_hora'=>'decimal:2',
        'profesores.profesores_horas_mes'=>'integer',
    ];

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){
            // $clasespruebas = ClasePrueba::orderBy($this->sort,$this->direction)
            //                             ->paginate($this->cant);
            $profesores = DB::table('profesores')
            ->orWhere('profesores.profesores_nombres','like','%'.trim($this->search).'%')
            ->orWhere('profesores.profesores_apellidos','like','%'.trim($this->search).'%')
            ->orWhere('profesores.profesores_email','like','%'.trim($this->search).'%')
            ->select('profesores.profesores_nombres','profesores.profesores_apellidos'
            ,'profesores.profesores_email','profesores.profesores_id','profesores.profesores_fecha_ingreso')
            ->paginate($this->cant);
            // $prospectos = Prospecto::where('prospectos_nombres','like','%'.trim($this->search).'%')
            //                        ->orWhere('prospectos_apellidos','like','%'.trim($this->search).'%')
            //                        ->orderBy($this->sort,$this->direction)
            //                        ->paginate($this->cant);
        } else {
            $profesores = array();
        }

        return view('livewire.show-profesores',['profesores'=>$profesores]);
    }

    public function loadPosts(){
        $this->readyToLoad = true;
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

    public function edit($id){
        $profesor = Profesor::find($id);
        $this->profesor = $profesor;
        $this->open_edit = true;
    }

    public function update(){
        $this->profesor->save();
        $this->reset(['open_edit']);
        $this->emit('alert','El profesor fue modificado satifactoriamente');

    }

    public function delete(Profesor $profesor){
        $profesor->delete();
        $this->emit('alert','El profesor fue eliminado satifactoriamente');
    }
}
