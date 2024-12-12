<?php

namespace App\Http\Livewire;

use App\Models\Grupo;
use App\Models\Estado;
use App\Models\Modalidad;
use App\Models\Espacio;
use App\Models\GruposDetalles;
use App\Models\Hora;
use App\Models\Dia;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ShowGrupos extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'grupo_id';
    public $direction = 'asc';
    public $grupo,$espacios_id,$dias_id,$horas_id;
    public $cant = 5;
    public $readyToLoad = false;
    public $detalles_grupos=array();
    public $borrados=array();

    public $open_edit = false;
    protected $listeners = ['render','delete','deleteDetalle'];

    protected $rules = [
        'grupo.grupo_nombre'=>'required|min:3|max:50',
        'grupo.grupo_nivel'=>'required|min:3|max:50',
        'grupo.grupo_capitulo'=>'required|numeric',
        'grupo.grupo_libro_maestro'=>'required|min:7|max:255',
        'grupo.grupo_libro_alumno'=>'required|min:7|max:255',
        'grupo.grupo_observacion'=>'required|min:7|max:255',
        'grupo.modalidad_id'=>'required',
        'grupo.estado_id'=>'required',
    ];

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){


            $grupos = DB::table('grupos')
                        ->select('grupo_id','grupo_nombre','grupo_nivel','grupo_capitulo',
                                 'grupo_libro_maestro','grupo_libro_alumno','grupo_observacion','modalidad_nombre','estado_nombre')
                        ->join('modalidades','grupos.modalidad_id','=','modalidades.modalidad_id')
                        ->join('estados','grupos.estado_id','=','estados.estado_id')
                        ->orWhere('grupos.grupo_nombre','like','%'.trim($this->search).'%')
                        ->orWhere('grupos.grupo_nivel','like','%'.trim($this->search).'%')
                        ->orWhere('grupos.grupo_capitulo','like','%'.trim($this->search).'%')
                        ->orWhere('modalidades.modalidad_nombre','like','%'.trim($this->search).'%')
                        ->orWhere('estados.estado_nombre','like','%'.trim($this->search).'%')
                        ->orderBy($this->sort,$this->direction)
                        ->paginate($this->cant);
        } else {
            $grupos = array();
        }
        $modalidades = Modalidad::all();
        $estados = Estado::all();
        $espacios = Espacio::all();
        $dias = Dia::all();
        $horas = Hora::all();
        return view('livewire.show-grupos',['grupos'=>$grupos
                                           ,'modalidades'=>$modalidades
                                           ,'espacios'=>$espacios
                                           ,'dias'=>$dias
                                           ,'horas'=>$horas
                                           ,'estados'=>$estados]);
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
        $grupo = Grupo::find($id);
        $detalles = GruposDetalles::where('grupo_id',$id)->get();
        $this->detalles_grupos=[];
        foreach ($detalles as $detalle) {
            $this->detalles_grupos[]=[
                'detalles_id'=>$detalle->detalles_id,
                'dias_id'=>$detalle->dias_id,
                'dia'=>$detalle->dia->dias_nombre,
                'horas_id'=>$detalle->horas_id,
                'hora'=>$detalle->hora->horas_desde .' - '.$detalle->hora->horas_hasta,
                'espacios_id'=>$detalle->espacios_id,
                'espacio'=>$detalle->espacio->espacios_nombre,
            ];
        }
        $this->grupo = $grupo;
        $this->open_edit = true;
    }

    public function update(){
        DB::beginTransaction();
        try {
            $this->grupo->save();
            foreach ($this->borrados as $borrar) {
                $deta = GruposDetalles::find($borrar);
                $deta->delete();
            }
            foreach ($this->detalles_grupos as $detalle) {
                if ($detalle['detalles_id'] == '0') {
                    $detalle = GruposDetalles::create([
                        'grupo_id' =>$this->grupo->grupo_id ,
                        'dias_id' =>$detalle['dias_id'] ,
                        'horas_id' =>$detalle['horas_id'],
                        'espacios_id' =>$detalle['espacios_id'],
                    ]);
                }
            }
            DB::commit();
            $this->reset(['open_edit','borrados','detalles_grupos']);
            $this->emit('alert','El grupo fue modificado satifactoriamente');
        } catch (\Throwable $th) {
            DB::rollBack(); // Revertir los cambios si algo falla
            $this->emit('alert','El grupo presento problema no fue agregado satifactoriamente','Error!','error');
        }
    }

    public function delete(Grupo $grupo){

        DB::beginTransaction();
        try {
            GruposDetalles::where('grupo_id',$grupo->grupo_id)->delete();
            $grupo->delete();
            $this->emit('alert','El grupo fue eliminado satifactoriamente');
        } catch (\Throwable $th) {
            DB::rollBack(); // Revertir los cambios si algo falla
            $this->emit('alert','El grupo presento problema no fue eliminado satifactoriamente','Error!','error');
        }
    }


    public function deleteDetalle($id){;
        $this->borrados[]=$this->detalles_grupos[$id]['detalles_id'];
        unset($this->detalles_grupos[$id]);
        $this->emit('alert','El horario fue eliminado satifactoriamente');
    }

    public function add(){
        $validatedData = $this->validate([
            'dias_id' => 'required',
            'horas_id' => 'required',
            'espacios_id' => 'required',
        ]);
        // Verifica si ya existe un registro con los mismos valores
        $existe = collect($this->detalles_grupos)->contains(function ($registro) use ($validatedData) {
            return $registro['dias_id'] === $validatedData['dias_id']
                && $registro['horas_id'] === $validatedData['horas_id']
                && $registro['espacios_id'] === $validatedData['espacios_id'];
        });

        if ($existe) {
            $this->addError('dias_id', "Ya existe en este grupo") ;
        } else {
            $existe = GruposDetalles::where('dias_id',$validatedData['dias_id'])
                                    ->where('horas_id',$validatedData['horas_id'])
                                    ->where('espacios_id',$validatedData['espacios_id'])->count();
            if ($existe > 0) {
                $this->addError('dias_id', "Ya existe en otro grupo.") ;
            } else {
                $dia = Dia::find($this->dias_id);
                $hora = Hora::find($this->horas_id);
                $espacio = Espacio::find($this->espacios_id);
                $this->detalles_grupos[]=[
                    'detalles_id'=>0,
                    'dias_id'=>$this->dias_id,
                    'dia'=>$dia->dias_nombre,
                    'horas_id'=>$this->horas_id,
                    'hora'=>$hora->horas_desde .' - '.$hora->horas_hasta,
                    'espacios_id'=>$this->espacios_id,
                    'espacio'=>$espacio->espacios_nombre,
                ];
                $this->reset(['dias_id','horas_id','espacios_id']); // Revertir los cambios si algo falla
            }
        }
    }

}
