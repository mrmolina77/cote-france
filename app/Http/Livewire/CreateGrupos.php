<?php

namespace App\Http\Livewire;

use App\Models\Grupo;
use App\Models\Estado;
use App\Models\GruposDetalles;
use App\Models\Hora;
use App\Models\Dia;
use App\Models\Modalidad;
use App\Models\Espacio;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateGrupos extends Component
{
    public $open = false;

    public $grupo_nombre,$grupo_nivel,$grupo_capitulo,$espacios;
    public $grupo_libro_maestro,$grupo_libro_alumno,$grupo_observacion,$modalidad_id;
    public $espacios_id,$dias_id,$horasid,$detalles_grupos=array();
    protected $listeners = ['render','createDelete'];

    protected $rules = [
        'grupo_nombre'=>'required|min:3|max:50',
        'grupo_nivel'=>'required|min:3|max:50',
        'grupo_capitulo'=>'required|numeric',
        'grupo_libro_maestro'=>'nullable|min:7|max:255',
        'grupo_libro_alumno'=>'nullable|min:7|max:255',
        'grupo_observacion'=>'nullable|min:7|max:255',
        'modalidad_id'=>'required',
    ];


    public function boot()
    {

        $this->espacios =  collect([]);
    }

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
            $grupo->estado_id           =1;
            $grupo->save();

            foreach ($this->detalles_grupos as $detalle) {
                $detalle = GruposDetalles::create([
                        'grupo_id' =>$grupo->grupo_id ,
                        'dias_id' =>$detalle['dias_id'] ,
                        'horasid' =>$detalle['horasid'],
                        'espacios_id' =>$detalle['espacios_id'],
                    ]);
            }
            DB::commit();
            $this->reset(['open','grupo_nombre','grupo_nivel','grupo_capitulo',
            'grupo_libro_maestro','grupo_libro_alumno','grupo_observacion','modalidad_id',
            'estado_id','espacios_id','detalles_grupos']);
            $this->emitTo('show-grupos','render');
            $this->emit('alert','El grupo fue agregado satifactoriamente');
        } catch (\Throwable $th) {
            DB::rollBack(); // Revertir los cambios si algo falla
            $this->emit('alert','El grupo presento problema no fue agregado satifactoriamente','Error!','error');
        }

    }

    public function add(){
        $validatedData = $this->validate([
            'dias_id' => 'required',
            'horasid' => 'required',
            'espacios_id' => 'required',
        ]);
        // Verifica si ya existe un registro con los mismos valores
        $existe = collect($this->detalles_grupos)->contains(function ($registro) use ($validatedData) {
            return $registro['dias_id'] === $validatedData['dias_id']
                && $registro['horasid'] === $validatedData['horasid']
                && $registro['espacios_id'] === $validatedData['espacios_id'];
        });

        if ($existe) {
            $this->addError('dias_id', "Ya existe en este grupo") ;
        } else {
            $existe = GruposDetalles::where('dias_id',$validatedData['dias_id'])
                                    ->where('horasid',$validatedData['horasid'])
                                    ->where('espacios_id',$validatedData['espacios_id'])->count();
            if ($existe > 0) {
                $this->addError('dias_id', "Ya existe en otro grupo.") ;
            } else {
                $dia = Dia::find($this->dias_id);
                $hora = Hora::find($this->horasid);
                $espacio = Espacio::find($this->espacios_id);
                $this->detalles_grupos[]=[
                                    'dias_id'=>$this->dias_id,
                                    'dia'=>$dia->dias_nombre,
                                    'horasid'=>$this->horasid,
                                    'hora'=>$hora->horas_desde .' - '.$hora->horas_hasta,
                                    'espacios_id'=>$this->espacios_id,
                                    'espacio'=>$espacio->espacios_nombre,
                                ];
                $this->reset(['dias_id','horasid','espacios_id']); // Revertir los cambios si algo falla
            }
        }
    }

    public function render()
    {
        $modalidades = Modalidad::all();
        $estados = Estado::all();
        $dias = Dia::all();
        $horas = Hora::all();
        $arr_niveles = array('A1'=>'A1','A2'=>'A2','B1'=>'B1','B2'=>'B2','C1'=>'C1','C2'=>'C2');
        $arr_capitulos = array('1er Capitulo'=>'1er Capitulo','2do Capitulo'=>'2do Capitulo','3er Capitulo'=>'3er Capitulo','4to Capitulo'=>'4to Capitulo','5to Capitulo'=>'5to Capitulo','6to Capitulo'=>'6to Capitulo','7mo Capitulo'=>'7mo Capitulo','8vo Capitulo'=>'8vo Capitulo','9no Capitulo'=>'9no Capitulo','10mo Capitulo'=>'10mo Capitulo');
        return view('livewire.create-grupos',['modalidades'=>$modalidades
                                             ,'estados'=>$estados
                                             ,'dias'=>$dias
                                             ,'horas'=>$horas
                                             ,'arr_niveles'=>$arr_niveles
                                             ,'arr_capitulos'=>$arr_capitulos]);
    }

    public function createDelete($id){;
        unset($this->detalles_grupos[$id]);
        $this->emit('alert','El horario fue eliminado satifactoriamente');
    }

    public function updatedhorasid($horasid){
        $espacios_registrados = GruposDetalles::where('horas_id',$horasid)->where('dias_id',$this->dias_id)->pluck('espacios_id');
        $this->espacios = Espacio::wherenotIn('espacios_id',$espacios_registrados)->get();
        if ($this->espacios->isEmpty()) {
            $this->addError('espacios_id', "No hay espacios disponibles para esta hora") ;
        }
    }
}
