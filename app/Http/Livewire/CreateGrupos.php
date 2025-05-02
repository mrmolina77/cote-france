<?php

namespace App\Http\Livewire;

use App\Models\Capitulo;
use App\Models\Grupo;
use App\Models\Estado;
use App\Models\GruposDetalles;
use App\Models\Hora;
use App\Models\Dia;
use App\Models\Modalidad;
use App\Models\Espacio;
use App\Models\Nivel;
use App\Models\Profesor;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateGrupos extends Component
{
    public $open = false;

    public $grupo_nombre,$idnivel,$id_capitulo,$espacios;
    public $grupo_libro_maestro,$grupo_libro_alumno,$grupo_observacion,$modalidad_id,$arr_horas;
    public $espacios_id,$diasid,$horasid,$detalles_grupos=array(),$arr_capitulos;
    protected $listeners = ['render','createDelete'];

    protected $rules = [
        'grupo_nombre'=>'required|min:3|max:45',
        'idnivel'=>'required',
        'id_capitulo'=>'required',
        'grupo_libro_maestro'=>'nullable|min:7|max:255',
        'grupo_libro_alumno'=>'nullable|min:7|max:255',
        'grupo_observacion'=>'nullable|min:7|max:255',
        'modalidad_id'=>'required',
    ];


    public function boot()
    {

        $this->espacios =  collect([]);
        $this->arr_capitulos = collect([]);
        $this->arr_horas = collect([]);
    }

    /* public function updated($propertyName){
        $this->validateOnly($propertyName);
    } */

    public function save(){
        $this->validate();
        DB::beginTransaction();
        try {
            // --- Inicio: Validación de profesores agregada ---
            if($this->modalidad_id == null || $this->modalidad_id == 0){
                $this->addError('modalidad_id', "No hay modalidad seleccionada para validar profesores.");
                DB::rollBack(); // Asegura que no se guarde nada si falla la validación
                return;
            }

            if($this->modalidad_id == 1){ // Asumiendo 1 = Presencial
                $cantidad_profesores = Profesor::where('modalidad_id',$this->modalidad_id)->count();
            } else { // Otras modalidades
                $cantidad_profesores = Profesor::count();
            }

            if ($cantidad_profesores == 0) {
                $this->addError('modalidad_id', "No hay profesores disponibles registrados para la modalidad seleccionada.");
                DB::rollBack(); // Asegura que no se guarde nada si falla la validación
                return;
            }
            // --- Fin: Validación de profesores agregada ---
            $grupo = new Grupo();
            $grupo->grupo_nombre        =$this->grupo_nombre;
            $grupo->nivel_id            =$this->idnivel;
            $grupo->capitulo_id         =$this->id_capitulo;
            $grupo->grupo_libro_maestro =$this->grupo_libro_maestro;
            $grupo->grupo_libro_alumno  =$this->grupo_libro_alumno;
            $grupo->grupo_observacion   =$this->grupo_observacion;
            $grupo->modalidad_id        =$this->modalidad_id;
            $grupo->estado_id           =1;
            $grupo->save();

            foreach ($this->detalles_grupos as $detalle) {
                $detalle = GruposDetalles::create([
                        'grupo_id' =>$grupo->grupo_id ,
                        'dias_id' =>$detalle['dias_id'] ,
                        'horas_id' =>$detalle['horas_id'],
                        'espacios_id' =>$detalle['espacios_id'],
                    ]);
            }
            DB::commit();
            $this->reset(['open','grupo_nombre','idnivel','id_capitulo',
            'grupo_libro_maestro','grupo_libro_alumno','grupo_observacion','modalidad_id',
            'espacios_id','detalles_grupos']);
            $this->espacios =  collect([]);
            $this->arr_capitulos = collect([]);
            $this->arr_horas = collect([]);
            $this->emitTo('show-grupos','render');
            $this->emit('alert','El grupo fue agregado satifactoriamente');
        } catch (\Throwable $th) {
            DB::rollBack(); // Revertir los cambios si algo falla
            $this->emit('alert','El grupo presento problema no fue agregado satifactoriamente'.$th->getMessage(),'Error!','error');
        }

    }

    public function add(){
        $validatedData = $this->validate([
            'diasid' => 'required',
            'horasid' => 'required',
            'espacios_id' => 'required',
        ]);

        // Verifica si ya existe un registro con los mismos valores
        if($this->modalidad_id == null || $this->modalidad_id == 0){
            $this->addError('modalidad_id', "No hay modalidad seleccionada");
            return;
        }

        if($this->modalidad_id == 1){
            $cantidad_profesores = Profesor::where('modalidad_id',$this->modalidad_id)->count();
        } else {
            $cantidad_profesores = Profesor::count();
        }

        if ($cantidad_profesores == 0) {
            $this->addError('espacios_id', "No hay profesores disponibles para este espacio");
            return;
        }
        $cantidad_grupos = GruposDetalles::where('dias_id',$validatedData['diasid'])
                                    ->where('horas_id',$validatedData['horasid'])->count();
        if( $cantidad_profesores <= $cantidad_grupos){
            $this->addError('diasid', "No hay susficientes profesores para este horario");
        } else {
            $existe = collect($this->detalles_grupos)->contains(function ($registro) use ($validatedData) {
                return $registro['dias_id'] === $validatedData['diasid']
                    && $registro['horas_id'] === $validatedData['horasid']
                    && $registro['espacios_id'] === $validatedData['espacios_id'];
            });

            if ($existe) {
                $this->addError('diasid', "Ya existe en este grupo") ;
            } else {
                $existe = GruposDetalles::where('dias_id',$validatedData['diasid'])
                                        ->where('horas_id',$validatedData['horasid'])
                                        ->where('espacios_id',$validatedData['espacios_id'])->count();
                if ($existe > 0) {
                    $this->addError('diasid', "Ya existe en otro grupo.") ;
                } else {
                    $dia = Dia::find($this->diasid);
                    $hora = Hora::find($this->horasid);
                    $espacio = Espacio::find($this->espacios_id);
                    $this->detalles_grupos[]=[
                                        'dias_id'=>$this->diasid,
                                        'dia'=>$dia->dias_nombre,
                                        'horas_id'=>$this->horasid,
                                        'hora'=>$hora->horas_desde .' - '.$hora->horas_hasta,
                                        'espacios_id'=>$this->espacios_id,
                                        'espacio'=>$espacio->espacios_nombre,
                                    ];
                    $this->reset(['diasid','horasid','espacios_id']); // Revertir los cambios si algo falla
                }
            }
        }
    }

    public function render()
    {
        $modalidades = Modalidad::all();
        $estados = Estado::all();
        $dias = Dia::all();
        $arr_niveles = Nivel::all()->pluck('nivel_descripcion','nivel_id');
        return view('livewire.create-grupos',['modalidades'=>$modalidades
                                             ,'estados'=>$estados
                                             ,'dias'=>$dias
                                             ,'arr_niveles'=>$arr_niveles]);
    }

    public function createDelete($id){;
        unset($this->detalles_grupos[$id]);
        $this->emit('alert','El horario fue eliminado satifactoriamente');
    }

    public function updatedhorasid($horasid){
        $espacios_registrados = GruposDetalles::where('horas_id', $horasid)
                                              ->where('dias_id', $this->diasid)
                                              ->pluck('espacios_id');
        $this->espacios = Espacio::whereNotIn('espacios_id', $espacios_registrados)->get();
        if ($this->espacios->isEmpty()) {
            $this->addError('espacios_id', "No hay espacios disponibles para esta hora");
        }

        // $this->arr_horas = Hora::where('horas_id', $horasid)->pluck('horas_desde', 'horas_hasta');
        if ($this->arr_horas->isEmpty()) {
            $this->addError('horasid', "No hay horas disponibles para esta selección");
        }
    }

    public function updatedidnivel($idnivel){

        $this->arr_capitulos = Capitulo::where('nivel_id',$idnivel)->get();
        if ($this->arr_capitulos->isEmpty()) {
            $this->addError('id_capitulo', "No hay capitulos disponibles para este nivel") ;
        }
    }

    public function updateddiasid($diasid){
        if ($diasid == 6) {
            $this->arr_horas = Hora::where('tipo', 2)->get();
        } else {
            $this->arr_horas = Hora::where('tipo', 1)->get();
        }

        if ($this->arr_horas->isEmpty()) {
            $this->addError('horasid', "No hay horas disponibles para este dia") ;
        }

    }

}
