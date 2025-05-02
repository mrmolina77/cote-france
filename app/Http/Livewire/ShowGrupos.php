<?php

namespace App\Http\Livewire;

use App\Models\Capitulo;
use App\Models\Grupo;
use App\Models\Estado;
use App\Models\Modalidad;
use App\Models\Espacio;
use App\Models\GruposDetalles;
use App\Models\Hora;
use App\Models\Dia;
use App\Models\Nivel;
use App\Models\Profesor;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ShowGrupos extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'grupo_id';
    public $direction = 'asc';
    public $grupo,$espacios_id,$dias_id,$horasid,$nivelid,$diasid;
    public $cant = 25;
    public $readyToLoad = false;
    public $detalles_grupos=array(),$espacios,$arr_capitulos,$arr_horas,$arr_niveles;
    public $borrados=array();

    public $open_edit = false;
    protected $listeners = ['render','delete','deleteDetalle'];

    protected $rules = [
        'grupo.grupo_nombre'=>'required|min:3|max:50',
        'grupo.nivel_id'=>'required',
        'grupo.capitulo_id'=>'required',
        'grupo.grupo_libro_maestro'=>'nullable|min:7|max:255',
        'grupo.grupo_libro_alumno'=>'nullable|min:7|max:255',
        'grupo.grupo_observacion'=>'nullable|min:7|max:255',
        'grupo.modalidad_id'=>'required',
        'grupo.estado_id'=>'required',
    ];


    public function boot()
    {

        $this->espacios =  collect([]);
        $this->arr_capitulos = collect([]);
        $this->arr_horas = collect([]);
    }

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){
            $grupos = DB::table('grupos')
                        ->select('grupo_id','grupo_nombre','nivel_descripcion','capitulo_descripcion',
                                 'grupo_libro_maestro','grupo_libro_alumno','grupo_observacion','modalidad_nombre','estado_nombre')
                        ->join('modalidades','grupos.modalidad_id','=','modalidades.modalidad_id')
                        ->join('estados','grupos.estado_id','=','estados.estado_id')
                        ->join('niveles','grupos.nivel_id','=','niveles.nivel_id')
                        ->join('capitulos','grupos.capitulo_id','=','capitulos.capitulo_id')
                        ->orWhere('grupos.grupo_nombre','like','%'.trim($this->search).'%')
                        ->orWhere('niveles.nivel_descripcion','like','%'.trim($this->search).'%')
                        ->orWhere('capitulos.capitulo_descripcion','like','%'.trim($this->search).'%')
                        ->orWhere('modalidades.modalidad_nombre','like','%'.trim($this->search).'%')
                        ->orWhere('estados.estado_nombre','like','%'.trim($this->search).'%')
                        ->orderBy($this->sort,$this->direction)
                        ->paginate($this->cant);
        } else {
            $grupos = array();
        }
        $modalidades = Modalidad::all();
        $estados = Estado::all();
        $espacios =  collect([]);
        $dias = Dia::all();

        $this->arr_niveles = Nivel::all()->pluck('nivel_descripcion','nivel_id');
        return view('livewire.show-grupos',['grupos'=>$grupos
                                           ,'modalidades'=>$modalidades
                                           ,'espacios'=>$espacios
                                           ,'dias'=>$dias
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
        $detalles = GruposDetalles::where('grupo_id',$id)->orderBy('dias_id','asc')->orderBy('horas_id','asc')->get();
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
        $this->nivelid = $grupo->nivel_id;
        $this->grupo = $grupo;
        $this->arr_capitulos = Capitulo::where('nivel_id',$grupo->nivel_id)->get();

        $this->open_edit = true;
    }

    public function update(){
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
            $this->emit('alert','El grupo presento problema no fue agregado satifactoriamente'.$th->getMessage(),'Error!','error');
        }
    }

    public function delete(Grupo $grupo){

        DB::beginTransaction();
        try {
            GruposDetalles::where('grupo_id',$grupo->grupo_id)->delete();
            $grupo->delete();
            DB::commit();
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
            'diasid' => 'required',
            'horasid' => 'required',
            'espacios_id' => 'required',
        ]);
        // Verifica si ya existe un registro con los mismos valores
        $existe = collect($this->detalles_grupos)->contains(function ($registro) use ($validatedData) {
            return $registro['dias_id'] === $validatedData['diasid']
                && $registro['horas_id'] === $validatedData['horasid']
                && $registro['espacios_id'] === $validatedData['espacios_id'];
        });

        // --- Inicio: Lógica adaptada de CreateGrupos ---
        // Verifica si la modalidad del grupo está definida
        if(is_null($this->grupo->modalidad_id) || $this->grupo->modalidad_id == 0){
            // Aunque esto no debería pasar si el grupo ya existe, es una salvaguarda.
            $this->emit('alert', 'Error: La modalidad del grupo no está definida.', 'Error!', 'error');
            return;
        }

        // Cuenta profesores según la modalidad del grupo
        if($this->grupo->modalidad_id == 1){ // Asumiendo 1 = Presencial
            $cantidad_profesores = Profesor::where('modalidad_id', $this->grupo->modalidad_id)->count();
        } else { // Para otras modalidades (ej. Virtual), cuenta todos. Ajusta si es necesario.
            $cantidad_profesores = Profesor::count();
        }

        // Cuenta los detalles de grupo existentes para ese día y hora (en todos los grupos)
        $cantidad_grupos = GruposDetalles::where('dias_id',$validatedData['diasid'])
                                    ->where('horas_id',$validatedData['horasid'])->count();
        if( $cantidad_profesores <= $cantidad_grupos){
            $this->addError('modalidad_id', "No hay susficientes profesores para este modalidad") ;
        } else {
            if ($existe) {
        // --- Fin: Lógica adaptada de CreateGrupos ---
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
                        'detalles_id'=>0,
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

    public function updatedhorasid($horasid){
        $espacios_registrados = GruposDetalles::where('horas_id',$horasid)->where('dias_id',$this->dias_id)->pluck('espacios_id');
        $this->espacios = Espacio::wherenotIn('espacios_id',$espacios_registrados)->get();
        if ($this->espacios->isEmpty()) {
            $this->addError('espacios_id', "No hay espacios disponibles para esta hora") ;
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
