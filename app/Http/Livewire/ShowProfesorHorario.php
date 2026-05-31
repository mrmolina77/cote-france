<?php

namespace App\Http\Livewire;

use App\Models\Capitulo;
use App\Models\ClasePrueba;
use App\Models\Dia;
use App\Models\Diario;
use App\Models\Espacio;
use App\Models\Evaluacion;
use App\Models\ActiveWeek;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Plan;
use App\Models\Profesor;
use App\Models\Prospecto;
use App\Models\Tematica;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ShowProfesorHorario extends Component
{
    public $fecha, $ydiario;
    public $open_edit;
    public $open_edit_plan;
    public $open_edit_diario;
    public $horarios_dia, $espacios_id, $horas_id, $grupo_id;
    public $planes_horarios_id, $planes_descripcion;
    public $diarios_horarios_id,$diarios_hecho,$diarios_porhacer;
    public $tematica, $numero_clases;
    public $plan, $diario, $semanal, $year;
    public $semana, $inicio, $fin, $profesores_id;
    public $porcentajes, $dimenciones, $porcentaje = 0;
    public $ocupados, $modalidad, $arr_capitulos;
    public $arr_niveles, $arr_capitulos2;
    public $idnivel;
    public $id_capitulo;
    public $id_tematica;
    public $diarios_profesor = '';
    public $diarios_espacio = '';
    public $diario_contexto = '';
    public $estudiantes = [];
    public $asistencias = [];
    public $observaciones = [];
    public $evaluaciones = [];
    public $validado_datos_generales = false;
    public $validado_contenido_clase = false;
    public $validado_estudiantes = false;
    public $validado_prospectos = false;
    public $clasesPrueba = [];
    public $asistenciasPrueba = [];
    public $observacionesPrueba = [];
    protected $listeners = ['render', 'delete', 'scrollToBottom'];
    public $semana_activa = false;
    public $solo_profesor = false;
    public $arr_tematicas;
    public $plan_modal_grupo = 'Sin cargar';
    public $plan_modal_fecha = 'Sin cargar';
    public $plan_modal_hora = 'Sin cargar';

    public function boot()
    {
        $this->semanal = true;
        $this->fecha = Carbon::now();
        $this->ydiario = $this->fecha->isoFormat('Y-MM-DD');
        $this->year = $this->fecha->isoWeekYear;
        $this->semana = $this->fecha->weekOfYear;
        $this->inicio = $this->fecha->startOfWeek()->toDateString();
        $this->fin = $this->fecha->endOfWeek()->toDateString();
        $this->fecha = Carbon::now();
        $this->ydiario = $this->fecha->isoFormat('Y-MM-DD');
        $this->arr_capitulos = collect([]);
        $this->arr_tematicas = collect([]);
        $this->porcentajes[] = "100%";
        $this->porcentajes[] = "95%";
        $this->porcentajes[] = "90%";
        $this->porcentajes[] = "75%";
        $this->porcentajes[] = "50%";
        $this->dimenciones[] = "scale-100 -translate-x-0 -translate-y-0";
        $this->dimenciones[] = "scale-95 -translate-x-10 -translate-y-10";
        $this->dimenciones[] = "scale-90 -translate-x-20 -translate-y-20";
        $this->dimenciones[] = "scale-75 -translate-x-40 -translate-y-40";
        $this->dimenciones[] = "scale-50 -translate-x-80 -translate-y-80";
    }

    public function mount($modalidad)
    {
        $this->modalidad = $modalidad;
        $this->estudiantes = collect([]);
        $this->arr_capitulos = collect([]);
        $this->arr_tematicas = collect([]);
        $this->arr_niveles = Nivel::all()->pluck('nivel_descripcion', 'nivel_id');
    }

    public function updatedYdiario($value)
    {
        $this->fecha = Carbon::parse($value);
    }

    public function render()
    {
        $id_relacionado = auth()->user()->relacionados_id;

        $espacios = Espacio::all();
        $horas = Hora::where('tipo', 1)->orderBy('horas_id', 'asc')->get();
        $horas2 = Hora::where('tipo', 2)->orderBy('horas_id', 'asc')->get();

        $activeWeek = ActiveWeek::where('start_date', $this->inicio)
            ->where('is_active', true)
            ->first();
        $this->semana_activa = (bool) $activeWeek;

        $array_horario = [];
        $grupo_deta = [];
        $grupos = collect();
        $profesores = collect();
        $profesor_horarios = collect();

        if ($this->semana_activa) {
            $horarios = Horario::where('horarios_dia', '>=', $this->inicio)
                ->where('horarios_dia', '<=', $this->fin)
                ->with(['grupo.modalidad', 'profesor', 'espacio', 'diario'])
                ->orderBy('horarios_dia', 'asc')
                ->orderBy('horas_id', 'asc')
                ->orderBy('profesores_id', 'asc')
                ->get();

            if ($this->solo_profesor) {
                $horarios = $horarios->where('profesores_id', $id_relacionado);
            }

            $profesor_horarios = $horarios->where('profesores_id', $id_relacionado);
            $pendientesAnteriores = [];

            if ($horarios->isNotEmpty()) {
                $grupoIds = $horarios->pluck('grupo_id')->unique()->values();
                $horariosPreviosPorGrupo = Horario::whereIn('grupo_id', $grupoIds)
                    ->whereDate('horarios_dia', '<', $this->inicio)
                    ->with('diario')
                    ->orderBy('horarios_dia', 'desc')
                    ->orderBy('horas_id', 'desc')
                    ->get()
                    ->groupBy('grupo_id')
                    ->map->first();

                $horariosPorGrupo = $horarios->groupBy('grupo_id');
                foreach ($horariosPorGrupo as $grupoId => $horariosGrupo) {
                    $ordenados = $horariosGrupo->sortBy(function ($horario) {
                        return $horario->horarios_dia . '-' . $horario->horas_id . '-' . $horario->horarios_id;
                    });
                    $previo = $horariosPreviosPorGrupo->get($grupoId);

                    foreach ($ordenados as $horario) {
                        $pendientesAnteriores[$horario->horarios_id] = $previo && ! $previo->diario?->updated_at;
                        $previo = $horario;
                    }
                }
            }

            foreach ($horarios as $horario) {
                if ($horario->grupo->modalidad_id == 1) {
                    $color = 'bg-red-100';
                } else {
                    $color = 'bg-green-100';
                }

                $array_horario[$horario->horarios_dia][$horario->horas_id][$horario->profesores_id] = [
                    'nombre' => $horario->grupo->grupo_nombre,
                    'color' => $horario->profesor->profesores_color,
                    'espacios_id' => $horario->espacios_id,
                    'grupo_id' => $horario->grupo_id,
                    'espacio' => $horario->espacio->espacios_nombre,
                    'enlace' => $horario->espacio->espacios_enlace,
                    'modalidad' => $horario->espacio->modalidad_id,
                    'bgcolor' => $color,
                    'id' => $horario->horarios_id,
                    'origen' => $horario->origen,
                    'protegido' => (bool) $horario->protegido,
                    'diario_actualizado' => $horario->diario?->updated_at,
                    'diario_anterior_pendiente' => $pendientesAnteriores[$horario->horarios_id] ?? false,
                    'editable' => $horario->profesores_id == $id_relacionado // Add editable flag
                ];
            }

            $this->ocupados = array();
            $grupo_deta = $this->cargaDetalleGrupo();
            $grupos = Grupo::where('modalidad_id', $this->modalidad)->where('estado_id', 1)->get();
            $profesoresQuery = Profesor::where('modalidad_id', $this->modalidad)
                ->where('profesores_activo', 1);

            if ($this->solo_profesor) {
                $profesoresQuery->where('profesores_id', $id_relacionado);
            }

            $profesores = $profesoresQuery->get();
        }
        $dias = Dia::take(5)->get();
        $dias2 = Dia::offset(5)->limit(5)->get();

        return view('livewire.show-profesor-horario', [
            'espacios' => $espacios,
            'horas' => $horas,
            'horas2' => $horas2,
            'horarios' => $array_horario,
            'profesor_horarios' => $profesor_horarios,
            'grupos' => $grupos,
            'grupo_deta' => $grupo_deta,
            'profesores' => $profesores,
            'id_relacionado' => $id_relacionado,
            'dias' => $dias,
            'dias2' => $dias2,
            'fecha' => $this->fecha
        ]);
    }

    public function anterior()
    {
        $this->fecha = $this->fecha->subWeek();
        $this->ydiario = $this->fecha->isoFormat('Y-MM-DD');
        $this->year = $this->fecha->isoWeekYear;
        $this->semana = $this->fecha->weekOfYear;
        $this->inicio = $this->fecha->startOfWeek()->toDateString();
        $this->fin = $this->fecha->endOfWeek()->toDateString();
    }

    public function siguiente()
    {
        $this->fecha = $this->fecha->addWeek();
        $this->ydiario = $this->fecha->isoFormat('Y-MM-DD');
        $this->year = $this->fecha->isoWeekYear;
        $this->semana = $this->fecha->weekOfYear;
        $this->inicio = $this->fecha->startOfWeek()->toDateString();
        $this->fin = $this->fecha->endOfWeek()->toDateString();
    }

    public function toggleSoloProfesor()
    {
        $this->solo_profesor = ! $this->solo_profesor;
    }

    public function delete(Horario $horario)
    {
        $id_relacionado = auth()->user()->relacionados_id;

        // Only allow deleting if it's the logged-in professor's schedule
        if ($horario->profesores_id != $id_relacionado) {
            $this->emit('alert', 'Solo puedes eliminar tu propio horario', 'Advertencia', 'warning');
            return;
        }

        $evaluaciones = Evaluacion::where('horarios_id', $horario->horarios_id)->exists();

        if ($evaluaciones) {
            $this->emit('alert', 'No se puede eliminar el horario porque tiene evaluaciones asociadas', 'Advertencias!', 'warning');
            return;
        }

        if ($horario->origen === 'manual') {
            Log::info('[HorariosProtegidos] Intento de eliminar clase manual bloqueado por profesor', [
                'horarios_id' => $horario->horarios_id,
                'grupo_id' => $horario->grupo_id,
                'horarios_dia' => $horario->horarios_dia,
                'horas_id' => $horario->horas_id,
                'origen' => $horario->origen,
                'protegido' => $horario->protegido,
            ]);

            $this->emit('alert', 'No se puede eliminar una clase creada manualmente.', 'Advertencias!', 'warning');
            return;
        }

        $horario->delete();
        $this->emit('alert', 'El horario fue eliminado satisfactoriamente');
    }

    public function editPlan($id)
    {
        $horarioBase = Horario::with(['grupo', 'hora'])->findOrFail($id);
        $this->plan_modal_grupo = $horarioBase->grupo?->grupo_nombre ?: 'Sin cargar';
        $this->plan_modal_fecha = $horarioBase->horarios_dia ? Carbon::parse($horarioBase->horarios_dia)->format('d-m-Y') : 'Sin cargar';
        $this->plan_modal_hora = $horarioBase->hora?->horas_desde ? Carbon::parse($horarioBase->hora->horas_desde)->format('H:i') : 'Sin cargar';

        // Buscamos todos los horarios del mismo grupo, en cualquier día y hora, anteriores o iguales a hoy
        $horarios = Horario::with(['diario.nivel', 'diario.capitulo', 'diario.tematica', 'profesor', 'espacio'])
            ->where('grupo_id', $horarioBase->grupo_id)
            ->whereDate('horarios_dia', '<=', $horarioBase->horarios_dia)
            ->orderBy('horarios_dia', 'desc')
            ->get();

        if ($horarios->isEmpty()) {
            $this->emit('alert', 'No hay datos que mostrar', 'Advertencias!', 'warning');
            return;
        }

        $horariosRelacionados = $horarios->pluck('horarios_id');

        // Traemos las evaluaciones y clases de prueba
        $evaluaciones = Evaluacion::with(['prospecto', 'horario.diario.nivel', 'horario.diario.capitulo', 'horario.diario.tematica', 'horario.profesor', 'horario.espacio'])
            ->whereIn('horarios_id', $horariosRelacionados)
            ->get();

        $clasesPrueba = ClasePrueba::with(['prospecto', 'horario.diario.nivel', 'horario.diario.capitulo', 'horario.diario.tematica', 'horario.profesor', 'horario.espacio'])
            ->where(function ($query) use ($horariosRelacionados, $horarios) {
                $query->whereIn('horarios_id', $horariosRelacionados);
                foreach ($horarios as $h) {
                    $query->orWhere(function ($subQuery) use ($h) {
                        $subQuery->whereNull('horarios_id')
                            ->whereDate('horarios_dia', $h->horarios_dia)
                            ->where('horas_id', $h->horas_id)
                            ->where('grupo_id', $h->grupo_id);
                    });
                }
            })
            ->where('estado', '!=', 'cancelada')
            ->get();

        foreach ($clasesPrueba as $clase) {
            if (is_null($clase->horarios_id)) {
                $matchingHorario = $horarios->first(function ($h) use ($clase) {
                    return \Carbon\Carbon::parse($h->horarios_dia)->toDateString() === \Carbon\Carbon::parse($clase->horarios_dia)->toDateString()
                        && (int)$h->horas_id === (int)$clase->horas_id
                        && (int)$h->grupo_id === (int)$clase->grupo_id;
                });
                if ($matchingHorario) {
                    $clase->horarios_id = $matchingHorario->horarios_id;
                    $clase->setRelation('horario', $matchingHorario);
                }
            }
        }

        $merged = $evaluaciones->concat($clasesPrueba);
        $grouped = $merged->groupBy('horarios_id');

        // Construimos el array final de evaluaciones respetando el formato que espera el Blade
        $finalEvaluaciones = [];

        foreach ($horarios as $h) {
            $hId = $h->horarios_id;
            
            if ($grouped->has($hId) && $grouped->get($hId)->isNotEmpty()) {
                // Si hay evaluaciones reales o clases de prueba, las convertimos a array
                $finalEvaluaciones[$hId] = $grouped->get($hId)->values()->toArray();
            } else {
                // Si no hay evaluaciones, inyectamos un elemento dummy con toda la info de la relación
                $finalEvaluaciones[$hId] = [
                    [
                        'is_dummy' => true,
                        'prospecto' => null,
                        'asistio' => null,
                        'observacion' => '',
                        'horario' => [
                            'horarios_dia' => $h->horarios_dia instanceof \Carbon\Carbon ? $h->horarios_dia->toDateString() : $h->horarios_dia,
                            'profesor' => $h->profesor ? [
                                'profesores_nombres' => $h->profesor->profesores_nombres ?? '',
                                'profesores_apellidos' => $h->profesor->profesores_apellidos ?? '',
                            ] : null,
                            'espacio' => $h->espacio ? [
                                'espacios_nombre' => $h->espacio->espacios_nombre ?? 'N/A',
                            ] : null,
                            'diario' => $h->diario ? [
                                'diarios_id' => $h->diario->diarios_id,
                                'niveles_id' => $h->diario->niveles_id,
                                'capitulos_id' => $h->diario->capitulos_id,
                                'tematica_id' => $h->diario->tematica_id,
                                'numero_clases' => $h->diario->numero_clases,
                                'diarios_hecho' => $h->diario->diarios_hecho,
                                'diarios_porhacer' => $h->diario->diarios_porhacer,
                                'nivel' => $h->diario->nivel ? [
                                    'nivel_id' => $h->diario->nivel->nivel_id,
                                    'nivel_descripcion' => $h->diario->nivel->nivel_descripcion,
                                ] : null,
                                'capitulo' => $h->diario->capitulo ? [
                                    'capitulo_id' => $h->diario->capitulo->capitulo_id,
                                    'capitulo_descripcion' => $h->diario->capitulo->capitulo_descripcion,
                                    'capitulo_codigo' => $h->diario->capitulo->capitulo_codigo,
                                ] : null,
                                'tematica' => $h->diario->relationLoaded('tematica') && $h->diario->getRelation('tematica') ? [
                                    'tematica_id' => $h->diario->getRelation('tematica')->tematica_id,
                                    'tematica_descripcion' => $h->diario->getRelation('tematica')->tematica_descripcion,
                                ] : null,
                            ] : null,
                        ]
                    ]
                ];
            }
        }

        $this->evaluaciones = $finalEvaluaciones;

        $this->arr_niveles = Nivel::all()->pluck('nivel_descripcion','nivel_id');
        $arr_capitulos = Capitulo::all();

        $this->arr_capitulos2 = [];
        foreach ($arr_capitulos as $capitulo) {
            $this->arr_capitulos2[$capitulo->capitulo_id] = $capitulo->capitulo_descripcion . ' - ' . $capitulo->capitulo_codigo;
        }

        $this->emit('scrollToBottom');
        $this->open_edit_diario = false;
        $this->open_edit_plan = true;
    }

    public function editDiario($id)
    {
        $this->diario = Diario::where('horarios_id',$id)->first();

        $horario = Horario::where('horarios_id',$id)->first();
        $this->diarios_profesor = $horario->profesor->profesores_nombres .' '.$horario->profesor->profesores_apellidos;
        $grupoNombre = $horario->grupo?->grupo_nombre ?? 'Sin grupo';
        $fechaClase = Carbon::parse($horario->horarios_dia)->format('d/m/Y');
        $horaDesde = $horario->hora?->horas_desde ? Carbon::parse($horario->hora->horas_desde)->format('H:i') : '--:--';
        $horaHasta = $horario->hora?->horas_hasta ? Carbon::parse($horario->hora->horas_hasta)->format('H:i') : '--:--';
        $this->diario_contexto = "Grupo {$grupoNombre} | {$fechaClase} | {$horaDesde} - {$horaHasta}";
        $this->diarios_espacio = $horario->espacio->espacios_nombre ?? 'N/A';
        $this->espacios_id = $horario->espacios_id;

        $grupoId = $horario->grupo_id;

        $grupo = Grupo::find($grupoId);

        $prospectos = Prospecto::whereHas('inscripciones', function ($query) use ($grupoId) {
            $query->where('grupo_id', $grupoId);
        })
        ->with('evaluaciones')
        ->get();

        if ($prospectos->isEmpty()) {
            $this->emit('alert', 'No hay estudiantes inscritos en este grupo', 'Advertencias!', 'warning');
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

        $clasesPruebaConHorario = ClasePrueba::with('prospecto')
            ->where('horarios_id', $horario->horarios_id)
            ->where('estado', '!=', 'cancelada')
            ->get();
        $clasesPruebaSinHorario = ClasePrueba::with('prospecto')
            ->whereNull('horarios_id')
            ->where(function ($query) use ($horario) {
                $query->where('grupo_id', $horario->grupo_id)
                      ->orWhere('profesores_id', $horario->profesores_id);
            })
            ->whereDate('horarios_dia', $horario->horarios_dia)
            ->where('horas_id', $horario->horas_id)
            ->where('estado', '!=', 'cancelada')
            ->get();
        $this->clasesPrueba = $clasesPruebaConHorario->merge($clasesPruebaSinHorario)->unique('clase_prueba_id')->values();
                foreach ($this->clasesPrueba as $clasePrueba) {
            Log::info('Clase de prueba cargada en actualizar diario', [
                'clase_prueba_id' => $clasePrueba->clase_prueba_id,
                'prospectos_id' => $clasePrueba->prospectos_id,
                'grupo_id' => $clasePrueba->grupo_id,
                'horarios_id' => $clasePrueba->horarios_id,
                'horarios_dia' => $clasePrueba->horarios_dia?->toDateString(),
                'horas_id' => $clasePrueba->horas_id,
            ]);
            $this->asistenciasPrueba[$clasePrueba->clase_prueba_id] = $clasePrueba->asistio;
            $this->observacionesPrueba[$clasePrueba->clase_prueba_id] = $clasePrueba->observacion;
                    }

        $this->diarios_hecho = $this->diario?->diarios_hecho ?? "";
        $this->diarios_porhacer = $this->diario?->diarios_porhacer ?? "";
        $this->tematica = $this->diario?->tematica ?? "";
        $this->numero_clases = $this->diario?->numero_clases;
        $nivelesid = $grupo->nivel_id;
        $capitulos_id = $grupo->capitulo_id;
        $this->idnivel = $this->diario?->niveles_id ?? $nivelesid;
        $this->arr_capitulos = Capitulo::where('nivel_id', $this->idnivel)->get();
        $this->id_capitulo = $this->diario?->capitulos_id ?? $capitulos_id;
        $this->arr_tematicas = Tematica::where('capitulo_id', $this->id_capitulo)
            ->where('tematica_activo', true)
            ->orderBy('tematica_descripcion')
            ->get();
        $this->id_tematica = $this->diario?->tematica_id;
        $this->validado_datos_generales = (bool) ($this->diario?->validado_datos_generales ?? false);
        $this->validado_contenido_clase = (bool) ($this->diario?->validado_contenido_clase ?? false);
        $this->validado_estudiantes = (bool) ($this->diario?->validado_estudiantes ?? false);
        $this->validado_prospectos = (bool) ($this->diario?->validado_prospectos ?? false);

        // dd($this->id_capitulo,$this->idnivel);

        $grupoId = $horario->grupo_id;
        $this->open_edit_plan = false;
        $this->open_edit_diario = true;
    }

    public function saveDiario()
    {
        $validated = $this->validate([
            'diarios_hecho'=>'required|min:15|max:550',
            'diarios_porhacer'=>'required|min:15|max:550',
            'idnivel'=>'required',
            'id_capitulo'=>'required',
            'id_tematica'=>'required',
            'numero_clases'=>'nullable|numeric|min:0.5',
        ]);

        try {
            DB::beginTransaction();
            $datosGeneralesValidados = (bool) ($this->validado_datos_generales && $this->idnivel && $this->id_capitulo);
            $numeroClases = $this->numero_clases === '' ? null : $this->numero_clases;

            foreach ($this->estudiantes as $estudiante) {
                $id = $estudiante->prospectos_id;
                $asistio = $this->asistencias[$id] ?? false;
                $observacion = $this->observaciones[$id] ?? null;

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

            if($this->diario){
                $this->diario->horarios_id = $this->diarios_horarios_id;
                $this->diario->diarios_hecho = $this->diarios_hecho;
                $this->diario->diarios_porhacer = $this->diarios_porhacer;
                $this->diario->niveles_id = $this->idnivel;
                $this->diario->capitulos_id = $this->id_capitulo;
                $this->diario->tematica_id = $this->id_tematica;
                $this->diario->numero_clases = $numeroClases;
                $this->diario->validado_datos_generales = $datosGeneralesValidados;
                $this->diario->validado_contenido_clase = (bool) $this->validado_contenido_clase;
                $this->diario->validado_estudiantes = (bool) $this->validado_estudiantes;
                $this->diario->validado_prospectos = (bool) $this->validado_prospectos;
                $this->diario->save();
            } else {
                $asistencia = Diario::create([
                    'horarios_id' => $this->diarios_horarios_id,
                    'diarios_hecho' => $this->diarios_hecho,
                    'diarios_porhacer' => $this->diarios_porhacer,
                    'niveles_id' => $this->idnivel,
                    'capitulos_id' => $this->id_capitulo,
                    'tematica_id' => $this->id_tematica,
                    'numero_clases' => $numeroClases,
                    'validado_datos_generales' => $datosGeneralesValidados,
                    'validado_contenido_clase' => (bool) $this->validado_contenido_clase,
                    'validado_estudiantes' => (bool) $this->validado_estudiantes,
                    'validado_prospectos' => (bool) $this->validado_prospectos,
                ]);

                $horario = Horario::where('horarios_id', $this->diarios_horarios_id)->first();
                $grupo = Grupo::find($horario->grupo_id);
                $grupo->nivel_id = $this->idnivel;
                $grupo->capitulo_id = $this->id_capitulo;
                $grupo->save();
            }

            $horarioActual = Horario::find($this->diarios_horarios_id);

            foreach ($this->clasesPrueba as $clasePrueba) {
                $asistio = $this->asistenciasPrueba[$clasePrueba->clase_prueba_id] ?? null;
                $estado = is_null($asistio) ? 'programada' : ((int) $asistio === 1 ? 'asistio' : 'falto');
                $clasePrueba->asistio = is_null($asistio) ? null : (int) $asistio;
                $clasePrueba->observacion = $this->observacionesPrueba[$clasePrueba->clase_prueba_id] ?? null;
                $clasePrueba->estado = $estado;

                if ($horarioActual) {
                    $clasePrueba->horarios_id = $horarioActual->horarios_id;
                    $clasePrueba->profesores_id = $horarioActual->profesores_id;
                    $clasePrueba->espacios_id = $this->espacios_id;
                    $clasePrueba->horarios_dia = $horarioActual->horarios_dia;
                    $clasePrueba->horas_id = $horarioActual->horas_id;
                    $clasePrueba->grupo_id = $horarioActual->grupo_id;
                }

                $clasePrueba->save();
                Log::info('Asistencia clase de prueba actualizada', [
                    'clase_prueba_id' => $clasePrueba->clase_prueba_id,
                    'prospectos_id' => $clasePrueba->prospectos_id,
                    'grupo_id' => $clasePrueba->grupo_id,
                    'horarios_id' => $clasePrueba->horarios_id,
                    'horarios_dia' => $clasePrueba->horarios_dia?->toDateString(),
                    'horas_id' => $clasePrueba->horas_id,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al guardar diario/evaluaciones/clases_prueba', ['error' => $e->getMessage()]);
            $this->emit('alert', 'Ocurrió un error al guardar el diario y asistencias.', 'Error!', 'error');
            return;
        }

        $this->reset(['open_edit_diario','diarios_horarios_id','diarios_hecho','diarios_porhacer','idnivel','id_capitulo','id_tematica']);
        $this->emit('alert','El diario fue actualización satisfactoriamente');
    }

    protected function cargaDetalleGrupo()
    {
        $id_relacionado = auth()->user()->relacionados_id;
        $grupo_deta = array();

        $horarios = Horario::where('horarios_dia', '>=', $this->inicio)
            ->where('horarios_dia', '<=', $this->fin)
            ->orderBy('horarios_dia', 'asc')
            ->orderBy('horas_id', 'asc')
            ->orderBy('profesores_id', 'asc')
            ->get();

        $array_horario = array();
        foreach ($horarios as $horario) {
            $array_horario[$horario->horarios_dia][$horario->horas_id][$horario->grupo_id][$horario->profesores_id] = $horario->horarios_id;
        }

        if ($this->modalidad == 2) {
            $detalles = DB::table('grupos_detalles')
                ->join('grupos', 'grupos_detalles.grupo_id', '=', 'grupos.grupo_id')
                ->where('grupos.modalidad_id', $this->modalidad)
                ->select('grupos_detalles.*', 'grupos.modalidad_id', 'grupos.grupo_nombre')
                ->orderBy('grupos_detalles.grupo_id', 'asc')
                ->orderBy('grupos_detalles.dias_id', 'asc')
                ->orderBy('grupos_detalles.horas_id', 'asc')
                ->get();
        } else {
            $detalles = DB::table('grupos_detalles')
                ->join('grupos', 'grupos_detalles.grupo_id', '=', 'grupos.grupo_id')
                ->select('grupos_detalles.*', 'grupos.modalidad_id', 'grupos.grupo_nombre')
                ->orderBy('grupos_detalles.grupo_id', 'asc')
                ->orderBy('grupos_detalles.dias_id', 'asc')
                ->orderBy('grupos_detalles.horas_id', 'asc')
                ->get();
        }

        $cantidad = [];
        foreach ($detalles as $item) {
            $evaluar = Carbon::parse($this->fecha)->setISODate($this->year, $this->semana, $item->dias_id)->isoFormat('YYYY-MM-DD');

            // Profesores disponibles para ese día (only the logged-in professor)
            $profesores = $this->obtenerProfesores($evaluar, $item->horas_id, $this->modalidad)
                ->where('profesores_id', $id_relacionado)
                ->values();

            if (!isset($array_horario[$evaluar][$item->horas_id][$item->grupo_id])) {
                if (!isset($cantidad[$evaluar][$item->horas_id])) {
                    $cantidad[$evaluar][$item->horas_id] = 0;
                }

                $index = $cantidad[$evaluar][$item->horas_id];

                if (isset($profesores[$index])) {
                    if ($item->modalidad_id == 1) {
                        $color = 'bg-red-100';
                    } else {
                        $color = 'bg-green-100';
                    }

                    $grupo_deta[$item->dias_id][$item->horas_id][$profesores[$index]] = [
                        'grupo_id' => $item->grupo_id,
                        'espacios_id' => $item->espacios_id,
                        'grupo_nombre' => $item->grupo_nombre,
                        'color' => $color,
                    ];

                    $cantidad[$evaluar][$item->horas_id]++;
                }
            }
        }

        return $grupo_deta;
    }

    public function obtenerProfesores($fecha, $hora, $modalidad_id)
    {
        $id_relacionado = auth()->user()->relacionados_id;

        $profesores = Profesor::whereNotIn('profesores_id', function ($query) use ($fecha, $hora) {
            $query->select('profesores_id')
                ->from('horarios')
                ->whereDate('horarios.horarios_dia', $fecha)
                ->where('horas_id', $hora);
        })
        ->where('modalidad_id', $modalidad_id)
        ->where('profesores_activo', 1)
        ->where('profesores_id', $id_relacionado) // Only the logged-in professor
        ->pluck('profesores_id');

        return $profesores;
    }

    public function initializeDragAndDrop()
    {
        $this->dispatchBrowserEvent('initialize-drag-and-drop');
    }

    public function updatedidnivel($idnivel)
    {
        $this->arr_capitulos = Capitulo::where('nivel_id', $idnivel)->get();
        $this->id_tematica = null;
        $this->arr_tematicas = collect([]);
        if ($this->arr_capitulos->isEmpty()) {
            $this->addError('id_capitulo', "No hay capitulos disponibles para este nivel");
        }
    }

    public function updatedIdCapitulo($capituloId)
    {
        $this->arr_tematicas = Tematica::where('capitulo_id', $capituloId)
            ->where('tematica_activo', true)
            ->orderBy('tematica_descripcion')
            ->get();

        if (! $this->arr_tematicas->pluck('tematica_id')->contains($this->id_tematica)) {
            $this->id_tematica = null;
        }
    }

    public function scrollToBottom()
    {
        $this->dispatchBrowserEvent('scrollToBottom');
    }
}
