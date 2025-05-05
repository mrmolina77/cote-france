<?php

namespace App\Http\Livewire;

use App\Models\Capitulo;
use App\Models\Dia;
use App\Models\Diario;
use App\Models\Espacio;
use App\Models\Evaluacion;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Plan;
use App\Models\Profesor;
use App\Models\Prospecto;
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
    public $diarios_horarios_id,$diarios_hecho,$diarios_porhacer;
    public $plan, $diario, $semanal,$year;
    public $semana,$inicio,$fin,$profesores_id;
    public $porcentajes, $dimenciones,$porcentaje = 0;
    public $ocupados, $modalidad, $arr_capitulos;
    public $arr_niveles, $arr_capitulos2;
    public $idnivel;
    public $id_capitulo;
    public $diarios_profesor = '';
    public $diarios_espacio = '';
    // $asistencias;
    // public $estudiantes;
    protected $listeners = ['render','delete','scrollToBottom'];
    public $estudiantes = [];
    public $asistencias = [];
    public $observaciones = [];
    public $evaluaciones = [];

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
        $this->arr_capitulos = collect([]);
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
        $this->estudiantes = collect([]);
        $this->arr_capitulos = collect([]);
        $this->arr_niveles = Nivel::all()->pluck('nivel_descripcion','nivel_id');
    }

    public function updatedYdiario($value)
    {
        // Actualiza la fecha cuando ydiario cambia
        $this->fecha = Carbon::parse($value);
    }


    public function render()
    {
        $espacios = Espacio::all();
        $horas = Hora::where('tipo',1)->orderBy('horas_id', 'asc')->get();
        $horas2 = Hora::where('tipo',2)->orderBy('horas_id', 'asc')->get();
        $horarios = Horario::where('horarios_dia','>=', $this->inicio)
        ->where('horarios_dia','<=', $this->fin)
        ->orderBy('horarios_dia', 'asc')
        ->orderBy('horas_id', 'asc')
        ->orderBy('profesores_id', 'asc')
        ->get();
        $array_horario = array();
        foreach ($horarios as $horario) {
            if($horario->grupo->modalidad_id == 1){
                $color = 'bg-green-100';
            } else {
                $color = 'bg-red-100';
            }
            $array_horario[$horario->horarios_dia][$horario->horas_id][$horario->profesores_id] = [ 'nombre'=>$horario->grupo->grupo_nombre
                                                                                                 ,'color'=>$horario->profesor->profesores_color
                                                                                                 ,'espacios_id'=>$horario->espacios_id
                                                                                                 ,'grupo_id'=>$horario->grupo_id
                                                                                                 ,'espacio'=>$horario->espacio->espacios_nombre
                                                                                                 ,'enlace'=>$horario->espacio->espacios_enlace
                                                                                                 ,'modalidad'=>$horario->espacio->modalidad_id
                                                                                                 ,'bgcolor'=>$color
                                                                                                 ,'id'=>$horario->horarios_id
                                                                                                ];
        }

        $this->ocupados=array();
        $grupo_deta=$this->cargaDetalleGrupo($this->modalidad);
        $grupos = Grupo::where('modalidad_id',$this->modalidad)->where('estado_id',1)->get();
        $profesores = Profesor::where('modalidad_id',$this->modalidad)->get();
        $dias = Dia::take(5)->get();
        $dias2 = Dia::offset(5)->limit(5)->get();
        // $this->porcentaje = 100 / (count($horas) * count($dias));
        return view('livewire.show-horarios',[
                                            'espacios'=>$espacios
                                           ,'horas'=>$horas
                                           ,'horas2'=>$horas2
                                           ,'horarios'=>$array_horario
                                           ,'grupos'=>$grupos
                                           ,'grupo_deta'=>$grupo_deta
                                           ,'profesores'=>$profesores
                                           ,'dias'=>$dias
                                           ,'dias2'=>$dias2
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
        // Verificar si el horario tiene evaluaciones asociadas
        $evaluaciones = Evaluacion::where('horarios_id', $horario->horarios_id)->exists();

        if ($evaluaciones) {
            $this->emit('alert', 'No se puede eliminar el horario porque tiene evaluaciones asociadas', 'Advertencias!', 'warning');
            return;
        }

        $horario->delete();
        $this->emit('alert', 'El horario fue eliminado satisfactoriamente');
    }

    public function editPlan($id)
    {
        $horarioBase = Horario::findOrFail($id);

        // Buscamos otros horarios del mismo grupo, en la misma hora y día de la semana, anteriores o iguales a hoy
        $horariosRelacionados = Horario::where('grupo_id', $horarioBase->grupo_id)
        ->where('horas_id', $horarioBase->horas_id)
        ->whereDate('horarios_dia', '<=', $horarioBase->horarios_dia)
        ->pluck('horarios_id');

        // Traemos las evaluaciones con sus relaciones
        $evaluaciones = Evaluacion::with(['prospecto', 'horario.diario', 'horario.profesor', 'horario.espacio']) // Carga las relaciones necesarias
            ->whereIn('horarios_id', $horariosRelacionados)
            ->get() // Obtiene todas las evaluaciones que coinciden
            ->groupBy('horarios_id');

        // Ordena los grupos por la fecha del horario (descendente - más reciente primero)
        $evaluaciones = $evaluaciones->sortByDesc(function ($items, $horarioId) {
            return $items->first()->horario->horarios_dia ?? null; // Usa la fecha del primer horario en el grupo para ordenar
        });


        if ($evaluaciones->isEmpty()) {
            $this->emit('alert', 'No hay datos que mostrar', 'Advertencias!', 'warning');
            return;
        }

        // Convertimos las colecciones anidadas a arrays planos para que Livewire los maneje bien
        $this->evaluaciones = $evaluaciones
        ->map(fn($items) => $items->values()->toArray())
        ->toArray();

        // dd($this->evaluaciones); // Asegúrate de comentar o quitar esto para ver el modal

        $this->arr_niveles = Nivel::all()->pluck('nivel_descripcion','nivel_id');
        $arr_capitulos = Capitulo::all();

        foreach ($arr_capitulos as $capitulo) {
            $this->arr_capitulos2[$capitulo->capitulo_id] = $capitulo->capitulo_descripcion . ' - ' . $capitulo->capitulo_codigo;
        }

        $this->emit('scrollToBottom'); // <-- Añade esta línea para emitir el evento
        $this->open_edit_plan = true;
    }


    public function editDiario($id){

        $this->diario = Diario::where('horarios_id',$id)->first();

        $horario = Horario::where('horarios_id',$id)->first();
        $this->diarios_profesor = $horario->profesor->profesores_nombres .' '.$horario->profesor->profesores_apellidos;
        $this->diarios_espacio = $horario->espacio->espacios_nombre;

        $grupoId = $horario->grupo_id;

        $grupo = Grupo::find($grupoId);

        $prospectos = Prospecto::whereHas('inscripciones', function($query) use ($grupoId) {
            $query->where('grupo_id', $grupoId);
        })
        ->with('evaluaciones')
        ->get();

        // dd($prospectos);


        if ($prospectos->isEmpty()) {
            $this->emit('alert', 'No hay estudiantes inscritos en este grupo', 'Advertencias!', 'warning');
            return;
        }

        $this->estudiantes = $prospectos;

        foreach ($prospectos as $prospecto) {
            // Intenta encontrar la evaluación específica para este horario
            $evaluacionEspecifica = $prospecto->evaluaciones
                ->where('horarios_id', $id)
                ->first();

            // Obtiene la asistencia de la evaluación específica o default a false
            $this->asistencias[$prospecto->prospectos_id] = $evaluacionEspecifica?->asistio ?? false;

            // Verifica si la evaluación específica para este día existe
            if ($evaluacionEspecifica) {
                // Si existe, usa su observación, incluso si es una cadena vacía
                $observacion = $evaluacionEspecifica->observacion ?? ''; // Usa la observación existente o '' si es null
            } else {
                // Si NO existe la evaluación para este día, busca la última observación no vacía de días anteriores
                $ultimaEvaluacionConObservacion = $prospecto->evaluaciones // Busca en todas las evaluaciones cargadas
                    ->whereNotNull('observacion') // Asegura que la observación no sea null
                    ->where('observacion', '!=', '') // Asegura que la observación no sea una cadena vacía
                    ->sortByDesc('horarios_id') // Ordena por ID de horario descendente (más reciente primero)
                    ->first(); // Obtiene la primera (la más reciente con observación)
                $observacion = $ultimaEvaluacionConObservacion?->observacion ?? ''; // Usa la última observación o default a vacío
            }

            $this->observaciones[$prospecto->prospectos_id] = $observacion;
        }

        $this->diarios_horarios_id = $id;
        $this->diarios_hecho = $this->diario?->diarios_hecho ?? "";
        $this->diarios_porhacer = $this->diario?->diarios_porhacer ?? "";
        $nivelesid = $grupo->nivel_id;
        $capitulos_id = $grupo->capitulo_id;
        $this->idnivel = $this->diario?->niveles_id ?? $nivelesid;
        $this->arr_capitulos = Capitulo::where('nivel_id', $this->idnivel)->get();
        $this->id_capitulo = $this->diario?->capitulos_id ?? $capitulos_id;

        // dd($this->id_capitulo,$this->idnivel);

        $grupoId = $horario->grupo_id;
        $this->open_edit_diario = true;
    }


    public function saveDiario(){
        $validated = $this->validate([
            'diarios_hecho'=>'required|min:15|max:550',
            'diarios_porhacer'=>'required|min:15|max:550',
            'idnivel'=>'required',
            'id_capitulo'=>'required',
        ]);

        // dd($this->idnivel,$this->id_capitulo);
        // Guardar o actualizar las evaluaciones de los estudiantes
        foreach ($this->estudiantes as $estudiante) {
            $id = $estudiante->prospectos_id; // o $estudiante->prospectos_id si ese es el nombre real

            // Toma los valores desde los arrays de inputs
            $asistio = $this->asistencias[$id] ?? false;
            $observacion = $this->observaciones[$id] ?? null;

            // Guarda o actualiza la evaluación del estudiante para este horario
            Evaluacion::updateOrCreate(
                [
                    'prospectos_id' => $id,
                    'horarios_id' => $this->diarios_horarios_id,
                ],
                [
                    'asistio' => $asistio,
                    'observacion' => $observacion,
                ]
            );
        }

        // Guardar o actualizar el diario
        if($this->diario){
            $this->diario->horarios_id = $this->diarios_horarios_id;
            $this->diario->diarios_hecho = $this->diarios_hecho;
            $this->diario->diarios_porhacer = $this->diarios_porhacer;
            $this->diario->niveles_id = $this->idnivel;
            $this->diario->capitulos_id = $this->id_capitulo;
            $this->diario->save();
        } else {
            $asistencia = Diario::create([
                'horarios_id' => $this->diarios_horarios_id,
                'diarios_hecho' => $this->diarios_hecho,
                'diarios_porhacer' => $this->diarios_porhacer,
                'niveles_id' => $this->idnivel,
                'capitulos_id' => $this->id_capitulo
            ]);
            // Guardar el nivel y capítulo en la tabla de grupos
            $horario = Horario::where('horarios_id', $this->diarios_horarios_id)->first();

            $grupo = Grupo::find($horario->grupo_id);
            $grupo->nivel_id = $this->idnivel;
            $grupo->capitulo_id = $this->id_capitulo;
            $grupo->save();
        }



        $this->reset(['open_edit_diario','diarios_horarios_id','diarios_hecho','diarios_porhacer','idnivel','id_capitulo']);
        $this->emit('alert','El diario fue actualización satisfactoriamente');
    }

    protected function cargaDetalleGrupo($modalidad){
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

       if($modalidad == 2){
           $detalles = DB::table('grupos_detalles')
                               ->join('grupos', 'grupos_detalles.grupo_id', '=', 'grupos.grupo_id')
                               ->where('grupos.modalidad_id', $modalidad)
                               ->select('grupos_detalles.*', 'grupos.modalidad_id', 'grupos.grupo_nombre') // Selecciona los campos que necesitas
                               ->orderBy('grupos_detalles.grupo_id', 'asc')
                               ->orderBy('grupos_detalles.dias_id', 'asc')
                               ->orderBy('grupos_detalles.horas_id', 'asc')
                               ->get();

            } else {
           $detalles = DB::table('grupos_detalles')
                               ->join('grupos', 'grupos_detalles.grupo_id', '=', 'grupos.grupo_id')
                               ->select('grupos_detalles.*', 'grupos.modalidad_id', 'grupos.grupo_nombre') // Selecciona los campos que necesitas
                               ->orderBy('grupos_detalles.grupo_id', 'asc')
                               ->orderBy('grupos_detalles.dias_id', 'asc')
                               ->orderBy('grupos_detalles.horas_id', 'asc')
                               ->get();
        }


        $cantidad=[];
        foreach ($detalles as $item) {
            // Fecha exacta del día de la semana del detalle
            $evaluar = Carbon::parse($this->fecha)->setISODate($this->year, $this->semana, $item->dias_id)->isoFormat('YYYY-MM-DD');

            // Profesores disponibles para ese día
            $profesores = $this->obtenerProfesores($evaluar,$item->horas_id, $modalidad)->values(); // asegura índices consecutivos

            // Si ese grupo ya tiene asignación para ese día y hora, lo saltamos
            if (!isset($array_horario[$evaluar][$item->horas_id][$item->grupo_id])) {

                // Inicializar contador para esa combinación día + hora
                if (!isset($cantidad[$evaluar][$item->horas_id])) {
                    $cantidad[$evaluar][$item->horas_id] = 0;
                }

                $index = $cantidad[$evaluar][$item->horas_id];

                if (isset($profesores[$index])) {
                    if($item->modalidad_id == 1){
                        $color = 'bg-green-100';
                    } else {
                        $color = 'bg-red-100';

                    }
                    $grupo_deta[$item->dias_id][$item->horas_id][$profesores[$index]] = [
                        'grupo_id' => $item->grupo_id,
                        'espacios_id' => $item->espacios_id,
                        'grupo_nombre' => $item->grupo_nombre,
                        'color' => $color,
                    ];
                    // Incrementar para el siguiente grupo en esta hora y día
                    $cantidad[$evaluar][$item->horas_id]++;
                }
            }
        }


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

    public function obtenerProfesores($fecha,$hora,$modalidad_id)
    {
        $profesores = Profesor::whereNotIn('profesores_id', function ($query) use ($fecha, $hora) {
            $query->select('profesores_id')
                ->from('horarios')
                ->whereDate('horarios.horarios_dia', $fecha)
                ->where('horas_id', $hora);
        })->where('modalidad_id',$modalidad_id)->pluck('profesores_id');
        return $profesores;
    }

    public function initializeDragAndDrop()
    {
        $this->dispatchBrowserEvent('initialize-drag-and-drop');
    }

    public function updatedidnivel($idnivel){

        $this->arr_capitulos = Capitulo::where('nivel_id',$idnivel)->get();
        if ($this->arr_capitulos->isEmpty()) {
            $this->addError('id_capitulo', "No hay capitulos disponibles para este nivel") ;
        }
    }

    public function scrollToBottom()
    {
        $this->dispatchBrowserEvent('scrollToBottom');
    }

}
