<?php

namespace App\Http\Livewire;

use App\Models\Curso;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Prospecto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ShowInscripciones extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'inscripciones_id';
    public $direction = 'asc';
    public $inscripcion;
    public $cant = 50;
    public $readyToLoad = false;
    public $prospectos = [];

    public $open_edit = false;
    protected $listeners = ['render','delete'];

    protected $rules = [
        'inscripcion.prospectos_id'=>'required|integer|exists:prospectos,prospectos_id',
        'inscripcion.cursos_id'=>'required|integer|exists:cursos,cursos_id',
        'inscripcion.grupo_id'=>'required|integer|exists:grupos,grupo_id',
        'inscripcion.fecha_inscripcion'=>'required|date',
    ];

    private const SORT_COLUMNS = [
        'inscripciones_id' => 'inscripciones.inscripciones_id',
        'fecha_inscripcion' => 'inscripciones.fecha_inscripcion',
        'cursos_id' => 'cursos.cursos_descripcion',
        'grupo_id' => 'grupos.grupo_nombre',
        'prospectos_id' => 'prospectos.prospectos_nombres',
    ];

    public function mount()
    {
        Gate::authorize('manage-inscripciones');
    }

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){
            // $inscripciones = Inscripcion::orderBy($this->sort,$this->direction)
            //                             ->paginate($this->cant);
            $inscripciones = DB::table('inscripciones')
            ->join('prospectos','prospectos.prospectos_id','=','inscripciones.prospectos_id')
            ->join('cursos','cursos.cursos_id','=','inscripciones.cursos_id')
            ->join('grupos','grupos.grupo_id','=','inscripciones.grupo_id')
            ->orWhere('prospectos.prospectos_nombres','like','%'.trim($this->search).'%')
            ->orWhere('prospectos.prospectos_apellidos','like','%'.trim($this->search).'%')
            ->orWhere('cursos.cursos_descripcion','like','%'.trim($this->search).'%')
            ->orWhere('inscripciones.fecha_inscripcion','like','%'.trim($this->search).'%')
            ->select('inscripciones.fecha_inscripcion','prospectos.prospectos_nombres'
            ,'prospectos.prospectos_apellidos','cursos.cursos_descripcion','grupos.grupo_nombre',
            'inscripciones.inscripciones_id')
            ->orderBy($this->sortColumn(), $this->safeDirection())
            ->paginate($this->cant);
            // $prospectos = Prospecto::where('prospectos_nombres','like','%'.trim($this->search).'%')
            //                        ->orWhere('prospectos_apellidos','like','%'.trim($this->search).'%')
            //                        ->orderBy($this->sort,$this->direction)
            //                        ->paginate($this->cant);
        } else {
            $inscripciones = array();
        }


        // dd($prospectos);
        $cursos = Curso::all();
        $grupos = Grupo::all();
        return view('livewire.show-inscripciones',['inscripciones'=>$inscripciones
                                                  , 'grupos'=>$grupos
                                                  , 'cursos'=>$cursos]);
    }

    public function loadPosts(){
        $this->readyToLoad = true;
    }

    public function order($order){
        if (! array_key_exists($order, self::SORT_COLUMNS)) {
            $this->sort = 'inscripciones_id';
            $this->direction = 'asc';

            return;
        }

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
        Gate::authorize('manage-inscripciones');
        $inscripcion = Inscripcion::findOrFail($id);
        $id_prospecto = $inscripcion->prospectos_id;
        $this->prospectos = Prospecto::whereNotIn('prospectos_id', function($query) {
            $query->select('prospectos_id')->from('inscripciones');
        })->orWhere('prospectos_id',$id_prospecto)->get();
        // $prospectos = Prospecto::whereNotIn('prospectos_id', function($query) {
        //     $query->select('prospectos_id')->from('inscripciones');
        // })->get();
        // dd($inscripcion);
        $this->inscripcion = $inscripcion;
        $this->open_edit = true;
    }

    public function update(){
        Gate::authorize('manage-inscripciones');
        $this->validate();

        if (Inscripcion::where('prospectos_id', $this->inscripcion->prospectos_id)
            ->where('inscripciones_id', '<>', $this->inscripcion->inscripciones_id)
            ->exists()) {
            $this->addError('inscripcion.prospectos_id', 'El prospecto ya cuenta con una inscripción.');

            return;
        }

        $this->inscripcion->save();
        $this->reset(['open_edit']);
        $this->emit('alert','La inscripción fue modificado satifactoriamente');

    }

    public function delete(Inscripcion $inscripcion){
        Gate::authorize('manage-inscripciones');
        $inscripcion->delete();
        $this->emit('alert','La inscripción fue eliminado satifactoriamente');
    }

    private function sortColumn()
    {
        return self::SORT_COLUMNS[$this->sort] ?? self::SORT_COLUMNS['inscripciones_id'];
    }

    private function safeDirection()
    {
        return in_array($this->direction, ['asc', 'desc'], true) ? $this->direction : 'asc';
    }

}
