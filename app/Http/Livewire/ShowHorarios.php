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
use Livewire\Component;

class ShowHorarios extends Component
{

    public $fecha;
    public $open_edit;
    public $open_edit_plan;
    public $open_edit_diario;
    public $horarios_dia,$espacios_id,$horas_id,$grupo_id;
    public $planes_horarios_id,$planes_descripcion;
    public $diarios_horarios_id,$diarios_descripcion;
    public $plan, $diario, $semanal,$year;
    public $semana,$inicio,$fin,$profesores_id;
    protected $listeners = ['render','delete'];

    public function boot()
    {
        $this->semanal = true;
        $this->fecha = Carbon::now();
        $this->year = Carbon::now()->isoFormat('Y');
        $this->semana = $this->fecha->weekOfYear;
        $this->inicio = $this->fecha->startOfWeek()->toDateString();
        $this->fin = $this->fecha->endOfWeek()->toDateString();
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
                                                                                                 ,'espacio'=>$horario->espacio->espacios_nombre
                                                                                                 ,'id'=>$horario->horarios_id
                                                                                                ];
        }

        $detalles = GruposDetalles::all();
        $grupo_deta=[];
        foreach ($detalles as $item) {
            $grupo_deta[$item->dias_id][$item->profesores_id][$item->horas_id]=['grupo_id'=>$item->grupo_id
                              ,'grupo_nombre'=>$item->grupo->grupo_nombre];
        }
        $grupos = Grupo::all();
        $profesores = Profesor::all();
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
}
