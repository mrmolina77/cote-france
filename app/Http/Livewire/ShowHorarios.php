<?php

namespace App\Http\Livewire;

use App\Models\Dia;
use App\Models\Diario;
use App\Models\Espacio;
use App\Models\Grupo;
use App\Models\GruposDetalles;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\Plan;
use App\Models\Profesor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ShowHorarios extends Component
{

    public $fecha,$ydiario;
    public $open_edit;
    public $open_edit_plan;
    public $open_edit_diario;
    public $horarios_dia,$espacios_id,$horas_id,$grupo_id;
    public $planes_horarios_id,$planes_descripcion;
    public $diarios_horarios_id,$diarios_descripcion;
    public $plan, $diario, $semanal,$year;
    public $semana,$inicio,$fin,$profesores_id;
    public $porcentajes, $dimenciones,$porcentaje;
    public $ocupados, $modalidad;
    protected $listeners = ['render','delete'];

    public function boot()
    {
        $this->semanal = true;
        $this->fecha = Carbon::now();
        $this->ydiario = $this->fecha->isoFormat('Y-MM-DD');
        // $this->fecha = new Carbon('last monday');
        $this->year = $this->fecha->isoFormat('Y');
        $this->semana = $this->fecha->weekOfYear;
        $this->inicio = $this->fecha->startOfWeek()->toDateString();
        $this->fin = $this->fecha->endOfWeek()->toDateString();
        $this->fecha = Carbon::now();
        $this->ydiario = $this->fecha->isoFormat('Y-MM-DD');
        $this->porcentajes[]="100%";
        $this->porcentajes[]="95%";
        $this->porcentajes[]="90%";
        $this->porcentajes[]="75%";
        $this->porcentajes[]="50%";
        $this->dimenciones[]="scale-100 -translate-x-0 -translate-y-0";
        $this->dimenciones[]="scale-95 -translate-x-10 -translate-y-10";
        $this->dimenciones[]="scale-90 -translate-x-20 -translate-y-20";
        $this->dimenciones[]="scale-75 -translate-x-40 -translate-y-40";
        $this->dimenciones[]="scale-50 -translate-x-80 -translate-y-80";
    }

    public function mount($modalidad){
        $this->modalidad = $modalidad;
    }

    public function updatedYdiario($value)
    {
        // Actualiza la fecha cuando ydiario cambia
        $this->fecha = Carbon::parse($value);
    }


    public function render()
    {
        $espacios = Espacio::all();
        $horas = Hora::all();
        $horarios = Horario::where('horarios_dia','>=', $this->inicio)
        ->where('horarios_dia','<=', $this->fin)
        ->orderBy('horarios_dia', 'asc')
        ->orderBy('horas_id', 'asc')
        ->orderBy('profesores_id', 'asc')
        ->get();
        $array_horario = array();
        foreach ($horarios as $horario) {
            $array_horario[$horario->horarios_dia][$horario->horas_id][$horario->profesores_id] = [ 'nombre'=>$horario->grupo->grupo_nombre
                                                                                                 ,'color'=>$horario->profesor->profesores_color
                                                                                                 ,'espacios_id'=>$horario->espacios_id
                                                                                                 ,'grupo_id'=>$horario->grupo_id
                                                                                                 ,'espacio'=>$horario->espacio->espacios_nombre
                                                                                                 ,'enlace'=>$horario->espacio->espacios_enlace
                                                                                                 ,'modalidad'=>$horario->espacio->modalidad_id
                                                                                                 ,'id'=>$horario->horarios_id
                                                                                                ];
        }

        $this->ocupados=array();
        $grupo_deta=$this->cargaDetalleGrupo();
        $grupos = Grupo::where('modalidad_id',$this->modalidad)->where('estado_id',1)->get();
        $profesores = Profesor::where('modalidad_id',$this->modalidad)->get();
        $dias = Dia::all();
        return view('livewire.show-horarios',[
                                            'espacios'=>$espacios
                                           ,'horas'=>$horas
                                           ,'horarios'=>$array_horario
                                           ,'grupos'=>$grupos
                                           ,'grupo_deta'=>$grupo_deta
                                           ,'profesores'=>$profesores
                                           ,'dias'=>$dias
                                           ,'fecha'=>$this->fecha
                                            ]);
    }

    public function edit($horarios_dia,$espacios_id,$horas_id,$profesores_id,$grupo_id=''){
        $this->horarios_dia = $horarios_dia;
        $this->espacios_id = $espacios_id;
        $this->horas_id = $horas_id;
        $this->grupo_id = $grupo_id;
        $this->profesores_id = $profesores_id;
        $this->open_edit = true;
    }

    public function anterior(){
        $this->fecha = $this->fecha->subWeek();
        $this->ydiario = $this->fecha->isoFormat('Y-MM-DD');
        $this->year = $this->fecha->isoFormat('Y');
        $this->semana = $this->fecha->weekOfYear;
        $this->inicio = $this->fecha->startOfWeek()->toDateString();
        $this->fin = $this->fecha->endOfWeek()->toDateString();
    }

    public function siguiente(){
        $this->fecha = $this->fecha->addWeek();
        $this->ydiario = $this->fecha->isoFormat('Y-MM-DD');
        $this->year = $this->fecha->isoFormat('Y');
        $this->semana = $this->fecha->weekOfYear;
        $this->inicio = $this->fecha->startOfWeek()->toDateString();
        $this->fin = $this->fecha->endOfWeek()->toDateString();
    }

    public function save(){
        $validated = $this->validate([
            'espacios_id'=>'required',
            'grupo_id'=>'required',
        ]);
        $prospecto = Horario::create([
            'horarios_dia' =>$this->horarios_dia,
            'espacios_id' =>$this->espacios_id,
            'horas_id' =>$this->horas_id,
            'grupo_id' =>$this->grupo_id,
            'profesores_id' =>$this->profesores_id
        ]);

        $this->reset(['open_edit','horarios_dia','espacios_id','grupo_id',
        'horas_id','profesores_id']);
        $this->emitTo('show-horarios','render');
        $this->emit('alert','El horario fue agregado satifactoriamente');
    }

    public function delete(Horario $horario){
        $horario->delete();
        $this->emit('alert','El horario fue eliminado satifactoriamente');
    }

    public function editPlan($id){
        $this->plan = Plan::where('horarios_id',$id)->first();
        if($this->plan){
            $this->planes_horarios_id = $id;
            $this->planes_descripcion = $this->plan->planes_descripcion;
        } else {
            $this->planes_horarios_id = $id;
            $this->planes_descripcion = "";

        }
        $this->open_edit_plan = true;
    }

    public function editDiario($id){
        $this->diario = Diario::where('horarios_id',$id)->first();
        if($this->diario){
            $this->diarios_horarios_id = $id;
            $this->diarios_descripcion = $this->diario->diarios_descripcion;
        } else {
            $this->diarios_horarios_id = $id;
            $this->diarios_descripcion = "";

        }
        $this->open_edit_diario = true;
    }
    public function savePlan(){
        $validated = $this->validate([
            'planes_descripcion'=>'required|min:15|max:550',
        ]);
        if($this->plan){
            $this->plan->horarios_id = $this->planes_horarios_id;
            $this->plan->planes_descripcion = $this->planes_descripcion;
            $this->plan->save();
        } else {
            $asistencia = Plan::create([
                'horarios_id' => $this->planes_horarios_id,
                'planes_descripcion' => $this->planes_descripcion,
            ]);
        }
        $this->reset(['open_edit_plan','planes_horarios_id','planes_descripcion']);
        $this->emit('alert','El plan fue actualización satisfactoriamente');
    }

    public function saveDiario(){
        $validated = $this->validate([
            'diarios_descripcion'=>'required|min:15|max:550',
        ]);
        if($this->diario){
            $this->diario->horarios_id = $this->diarios_horarios_id;
            $this->diario->diarios_descripcion = $this->diarios_descripcion;
            $this->diario->save();
        } else {
            $asistencia = Diario::create([
                'horarios_id' => $this->diarios_horarios_id,
                'diarios_descripcion' => $this->diarios_descripcion,
            ]);
        }
        $this->reset(['open_edit_diario','diarios_horarios_id','diarios_descripcion']);
        $this->emit('alert','El diario fue actualización satisfactoriamente');
    }

    protected function cargaDetalleGrupo(){
        $grupo_deta=array();
        $horarios = Horario::where('horarios_dia','>=', $this->inicio)
                           ->where('horarios_dia','<=', $this->fin)
                           ->orderBy('horarios_dia', 'asc')
                           ->orderBy('horas_id', 'asc')
                           ->orderBy('profesores_id', 'asc')
                           ->get();
        $array_horario = array();
        foreach ($horarios as $horario) {
            $array_horario[$horario->horarios_dia][$horario->horas_id][$horario->grupo_id][$horario->profesores_id] = $horario->horarios_id;
        }
        $detalles = GruposDetalles::all();
        $cantidad=[];
        foreach ($detalles as $item) {
            $evaluar = \Carbon\Carbon::parse($this->fecha)->setISODate($this->year, $this->semana, $item->dias_id)->isoFormat('YYYY-MM-DD');
            $proveedor = $this->obtenerProveedores($evaluar);
            // dd($proveedor);
            if(! isset($array_horario[$evaluar][$item->horas_id][$item->grupo_id])){
                if(!isset($cantidad[$evaluar])){
                    $cantidad[$evaluar]=0;
                } else {
                    $cantidad[$evaluar]+= 1;
                }
                $grupo_deta[$item->dias_id][$item->horas_id][$proveedor[$cantidad[$evaluar]]]=[
                                                'grupo_id'=>$item->grupo_id
                                               ,'espacios_id'=>$item->espacios_id
                                               ,'grupo_nombre'=>$item->grupo->grupo_nombre];
            }
        }

        // dd($grupo_deta);

        // $horas = Hora::all();
        // $profesores = Profesor::all();
        // $dias = Dia::all();
        // $respuesta=array();
        // $anterior=[];
        // $centros=[];
        // foreach ( $horas as $hora ){
        //     foreach ($dias as $dia) {
        //         if(isset($grupo_deta[$dia->dias_id][$hora->horas_id])){
        //             foreach ($grupo_deta[$dia->dias_id][$hora->horas_id] as $deta) {
        //                 $cantidad = count($grupo_deta[$dia->dias_id][$hora->horas_id]);
        //                 foreach ($profesores as $profesor) {
        //                     // dd($array_horario,\Carbon\Carbon::parse($this->fecha)->setISODate($this->year, $this->semana, $dia->dias_id)->isoFormat('YYYY-MM-DD'));
        //                     if (! isset($array_horario[\Carbon\Carbon::parse($this->fecha)->setISODate($this->year, $this->semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$deta['grupo_id']][$profesor->profesores_id])){
        //                         if (! isset($this->ocupados[$dia->dias_id][$hora->horas_id][$profesor->profesores_id])){
        //                             $respuesta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id]=['grupo_id'=>$deta['grupo_id']
        //                                                                                                  ,'espacios_id'=>$deta['espacios_id']
        //                                                                                                  ,'grupo_nombre'=>$deta['grupo_nombre']];
        //                             $this->ocupados[$dia->dias_id][$hora->horas_id][$profesor->profesores_id]='';
        //                             $anterior[]=$profesor->profesores_id;
        //                             $centros[]=$deta['grupo_id'];
        //                             print_r(' prueba 1 ');
        //                             var_dump($respuesta);
        //                             print_r(' - ');
        //                         } elseif ( ! in_array($profesor->profesores_id,$anterior) and ! in_array($deta['grupo_id'],$centros)) {
        //                             $respuesta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id]=['grupo_id'=>$deta['grupo_id']
        //                                                                                                  ,'espacios_id'=>$deta['espacios_id']
        //                                                                                                  ,'grupo_nombre'=>$deta['grupo_nombre']];
        //                             $this->ocupados[$dia->dias_id][$hora->horas_id]=$profesor->profesores_id;
        //                             print_r(' prueba 2 ');
        //                             var_dump($profesor->profesores_id);
        //                             print_r(' - ');
        //                             $anterior[]=$profesor->profesores_id;
        //                             $centros[]=$deta['grupo_id'];
        //                         }
        //                     }
        //                     if(count($anterior)>=$cantidad){
        //                         break 2;
        //                     }
        //                 }
        //             }
        //         }
        //     }

        // }
        return $grupo_deta;
    }

    public function updateGrupoHorario($horarios_id, $horarios_dia, $horas_id, $grupo_id, $profesores_id, $espacios_id, $anterior_id)
    {
        // dd($horarios_id, $horarios_dia, $horas_id, $grupo_id, $profesores_id, $espacios_id, $anterior_id);
        if ($grupo_id == '0') {
            $this->emit('alert', 'El horario está vacío, no se puede realizar esta operación', 'Advertencias!', 'warning');
        } elseif ($horarios_id != '0' && $horarios_id == $anterior_id) {
            $this->emit('alert', 'El horario es el mismo, no se puede realizar esta operación', 'Advertencias!', 'warning');
        } else {
            if ($anterior_id != '0') {
                $horario = Horario::find($anterior_id);
                if (!is_null($horario) && is_object($horario)) {
                    $horario->delete();
                    unset($horario);
                }
            }
            if ($horarios_id != '0') {
                $horario = Horario::find($horarios_id);
                                  ;
                if ($horario) {
                    $horario->update([
                        'horarios_dia' => $horarios_dia,
                        'horas_id' => $horas_id,
                        'grupo_id' => $grupo_id,
                        'espacios_id' => $espacios_id,
                        'profesores_id' => $profesores_id,
                    ]);
                } else {
                    $horario = Horario::create([
                        'horarios_dia' => $horarios_dia,
                        'horas_id' => $horas_id,
                        'grupo_id' => $grupo_id,
                        'espacios_id' => $espacios_id,
                        'profesores_id' => $profesores_id,
                    ]);
                }
            } else {
                $cantidad = Horario::where('horarios_dia',$horarios_dia)
                                  ->where('espacios_id',$espacios_id)
                                  ->where('horas_id',$horas_id)
                                  ->where('grupo_id',$grupo_id)
                                  ->where('profesores_id',$profesores_id)->count();
                                  ;
                if ($cantidad == 0) {
                    $horario = Horario::create([
                        'horarios_dia' => $horarios_dia,
                        'horas_id' => $horas_id,
                        'grupo_id' => $grupo_id,
                        'espacios_id' => $espacios_id,
                        'profesores_id' => $profesores_id,
                    ]);
                }
            }
            $this->emit('alert', 'El horario fue agregado satisfactoriamente');
        }
    }

    public function obtenerProveedores($fecha)
    {
        $profesores = Profesor::whereNotIn('profesores_id', function ($query) use ($fecha) {
            $query->select('profesores_id')
                ->from('horarios')
                ->whereDate('horarios.horarios_dia', $fecha);
        })->pluck('profesores_id');
        return $profesores;
    }

    public function initializeDragAndDrop()
    {
        $this->dispatchBrowserEvent('initialize-drag-and-drop');
    }

}
