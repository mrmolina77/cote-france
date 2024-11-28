<?php

namespace App\Http\Livewire;

use App\Models\Grupo;
use App\Models\Estado;
use App\Models\GruposDetalles;
use App\Models\Hora;
use App\Models\Dia;
use App\Models\Modalidad;
use App\Models\Profesor;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateGrupos extends Component
{
    public $open = false;

    public $grupo_nombre,$grupo_nivel,$grupo_capitulo;
    public $grupo_libro_maestro,$grupo_libro_alumno,$grupo_observacion,$modalidad_id;
    public $estado_id,$profesores_id,$dias_id,$horas_id;
    public $detalles_grupos=array();
    protected $listeners = ['render','delete'];

    protected $rules = [
        'grupo_nombre'=>'required|min:3|max:50',
        'grupo_nivel'=>'required|min:3|max:50',
        'grupo_capitulo'=>'required|numeric',
        'grupo_libro_maestro'=>'required|min:7|max:255',
        'grupo_libro_alumno'=>'required|min:7|max:255',
        'grupo_observacion'=>'required|min:7|max:255',
        'modalidad_id'=>'required',
        'estado_id'=>'required',
    ];



    /* public function updated($propertyName){
        $this->validateOnly($propertyName);
    } */

    public function save(){
        $this->validate();
        DB::beginTransaction();
        try {
            $grupo = new Grupo();
            $grupo->grupo_nombre        =$this->grupo_nombre;
            $grupo->grupo_nivel         =$this->grupo_nivel;
            $grupo->grupo_capitulo      =$this->grupo_capitulo;
            $grupo->grupo_libro_maestro =$this->grupo_libro_maestro;
            $grupo->grupo_libro_alumno  =$this->grupo_libro_alumno;
            $grupo->grupo_observacion   =$this->grupo_observacion;
            $grupo->modalidad_id        =$this->modalidad_id;
            $grupo->estado_id           =$this->estado_id;
            $grupo->save();

            foreach ($this->detalles_grupos as $detalle) {
                $detalle = GruposDetalles::create([
                        'grupo_id' =>$grupo->grupo_id ,
                        'dias_id' =>$detalle['dias_id'] ,
                        'horas_id' =>$detalle['horas_id'],
                        'profesores_id' =>$detalle['profesores_id'],
                    ]);
            }
            DB::commit();
            $this->reset(['open','grupo_nombre','grupo_nivel','grupo_capitulo',
            'grupo_libro_maestro','grupo_libro_alumno','grupo_observacion','modalidad_id',
            'estado_id','profesores_id','detalles_grupos']);
            $this->emitTo('show-grupos','render');
            $this->emit('alert','El grupo fue agregado satifactoriamente');
        } catch (\Throwable $th) {
            DB::rollBack(); // Revertir los cambios si algo falla
            dd($th);
            $this->emit('alert','El grupo presento problema no fue agregado satifactoriamente','Error!','error');
        }

    }

    public function add(){
        $validatedData = $this->validate([
            'dias_id' => 'required',
            'horas_id' => 'required',
            'profesores_id' => 'required',
        ]);
        // Verifica si ya existe un registro con los mismos valores
        $existe = collect($this->detalles_grupos)->contains(function ($registro) use ($validatedData) {
            return $registro['dias_id'] === $validatedData['dias_id']
                && $registro['horas_id'] === $validatedData['horas_id']
                && $registro['profesores_id'] === $validatedData['profesores_id'];
        });

        if ($existe) {
            $this->addError('dias_id', "Ya existe en este grupo") ;
        } else {
            $existe = GruposDetalles::where('dias_id',$validatedData['dias_id'])
                                    ->where('horas_id',$validatedData['horas_id'])
                                    ->where('profesores_id',$validatedData['profesores_id'])->count();
            if ($existe > 0) {
                $this->addError('dias_id', "Ya existe en otro grupo.") ;
            } else {
                $dia = Dia::find($this->dias_id);
                $hora = Hora::find($this->horas_id);
                $profesor = Profesor::find($this->profesores_id);
                $this->detalles_grupos[]=[
                                    'dias_id'=>$this->dias_id,
                                    'dia'=>$dia->dias_nombre,
                                    'horas_id'=>$this->horas_id,
                                    'hora'=>$hora->horas_desde .' - '.$hora->horas_hasta,
                                    'profesores_id'=>$this->profesores_id,
                                    'profesor'=>$profesor->profesores_nombres.' '.$profesor->profesores_apellidos,
                                ];
                $this->reset(['dias_id','horas_id','profesores_id']); // Revertir los cambios si algo falla
            }
        }
    }

    public function render()
    {
        $modalidades = Modalidad::all();
        $estados = Estado::all();
        $profesores = Profesor::all();
        $dias = Dia::all();
        $horas = Hora::all();
        return view('livewire.create-grupos',['modalidades'=>$modalidades
                                             ,'estados'=>$estados
                                             ,'dias'=>$dias
                                             ,'horas'=>$horas
                                             ,'profesores'=>$profesores]);
    }

    public function delete($id){;
        unset($this->detalles_grupos[$id]);
        $this->emit('alert','El horario fue eliminado satifactoriamente');
    }
}
