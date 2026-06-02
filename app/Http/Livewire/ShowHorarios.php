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
use App\Models\GrupoInactivo;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\Nivel;
use App\Models\Plan;
use App\Models\Profesor;
use App\Models\Prospecto;
use App\Models\Tematica;
use App\Models\BloqueosProfesores;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
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
    public $tematica, $numero_clases;
    public $plan, $diario, $semanal,$year;
    public $semana,$inicio,$fin,$profesores_id;
    public $porcentajes, $dimenciones,$porcentaje = 0;
    public $ocupados, $modalidad, $arr_capitulos;
    public $arr_niveles, $arr_capitulos2;
    public $idnivel, $id_espacios;
    public $id_capitulo;
    public $id_tematica;
    public $diarios_profesor = '';
    public $diarios_espacio = '';
    public $diario_contexto = '';
    public $espacios;
    public $arr_tematicas;
    public $semana_activa = false;
    // $asistencias;
    // public $estudiantes;
    protected $listeners = [
        'render',
        'delete',
        'scrollToBottom',
        'deactivateGrupo',
        'undoHorarioShortcut' => 'undoHorario',
        'redoHorarioShortcut' => 'redoHorario',
    ];
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
    public $open_create_clase_prueba = false;
    public $clase_prueba_prospectos_ids = [], $clase_prueba_grupo_id, $clase_prueba_horarios_dia, $clase_prueba_horas_id, $clase_prueba_profesores_id, $clase_prueba_espacios_id, $clase_prueba_modalidad_id, $clase_prueba_observacion;
    public $plan_modal_grupo = 'Sin cargar';
    public $plan_modal_fecha = 'Sin cargar';
    public $plan_modal_hora = 'Sin cargar';
    public array $undoStack = [];
    public array $redoStack = [];

    public function boot()
    {
        $this->semanal = true;
        $this->fecha = Carbon::now();
        $this->ydiario = $this->fecha->isoFormat('Y-MM-DD');
        // $this->fecha = new Carbon('last monday');
        $this->year = $this->fecha->isoWeekYear;
        $this->semana = $this->fecha->weekOfYear;
        $this->inicio = $this->fecha->startOfWeek()->toDateString();
        $this->fin = $this->fecha->endOfWeek()->toDateString();
        $this->fecha = Carbon::now();
        $this->ydiario = $this->fecha->isoFormat('Y-MM-DD');
        $this->arr_capitulos = collect([]);
        $this->arr_tematicas = collect([]);
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


    private function logHorarioDebug(string $evento, array $data = []): void
    {
        Log::info('[HorariosManualDebug] ' . $evento, array_merge([
            'component' => static::class,
            'user_id' => optional(auth())->id(),
            'timestamp' => now()->toDateTimeString(),
        ], $data));
    }

    private function horarioDebugSnapshot(?Horario $horario): ?array
    {
        if (! $horario) {
            return null;
        }

        return [
            'horarios_id' => $horario->horarios_id,
            'grupo_id' => $horario->grupo_id,
            'profesores_id' => $horario->profesores_id,
            'horarios_dia' => $horario->horarios_dia instanceof Carbon
                ? $horario->horarios_dia->format('Y-m-d')
                : $horario->horarios_dia,
            'horas_id' => $horario->horas_id,
            'espacios_id' => $horario->espacios_id,
            'origen' => $horario->origen ?? null,
            'protegido' => $horario->protegido ?? null,
            'protegido_at' => $horario->protegido_at ?? null,
        ];
    }

    public function undoHorario(): void
    {
        if (empty($this->undoStack)) {
            $this->emit('alert', 'No hay cambios para deshacer.', 'Información', 'info');
            return;
        }

        $action = array_pop($this->undoStack);

        Log::info('[HorarioUndo] Intentando deshacer', [
            'type' => $action['type'] ?? null,
            'horario_id' => $action['horario_id'] ?? null,
            'undo_count' => count($this->undoStack),
            'redo_count' => count($this->redoStack),
        ]);

        try {
            $redoAction = $this->applyHorarioAction($action, 'undo');

            if ($redoAction) {
                $this->redoStack[] = $redoAction;
            }

            $this->emitTo('show-horarios', 'render');
            $this->emitSelf('$refresh');
            $this->emit('alert', 'Cambio deshecho correctamente');

            Log::info('[HorarioUndo] Undo aplicado', [
                'type' => $action['type'] ?? null,
                'horario_id' => $redoAction['horario_id'] ?? $action['horario_id'] ?? null,
                'undo_count' => count($this->undoStack),
                'redo_count' => count($this->redoStack),
            ]);
        } catch (RuntimeException $e) {
            $this->undoStack[] = $action;
            $this->emit('alert', $e->getMessage(), 'Advertencia!', 'warning');
        } catch (\Throwable $e) {
            $this->undoStack[] = $action;
            Log::error('[HorarioUndo] Error', [
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->emit('alert', 'No se pudo deshacer el cambio.', 'Error!', 'error');
        }
    }

    public function redoHorario(): void
    {
        if (empty($this->redoStack)) {
            $this->emit('alert', 'No hay cambios para rehacer.', 'Información', 'info');
            return;
        }

        $action = array_pop($this->redoStack);

        Log::info('[HorarioRedo] Intentando rehacer', [
            'type' => $action['type'] ?? null,
            'horario_id' => $action['horario_id'] ?? null,
            'undo_count' => count($this->undoStack),
            'redo_count' => count($this->redoStack),
        ]);

        try {
            $undoAction = $this->applyHorarioAction($action, 'redo');

            if ($undoAction) {
                $this->undoStack[] = $undoAction;
            }

            $this->emitTo('show-horarios', 'render');
            $this->emitSelf('$refresh');
            $this->emit('alert', 'Cambio rehecho correctamente');

            Log::info('[HorarioRedo] Acción aplicada', [
                'type' => $action['type'] ?? null,
                'horario_id' => $undoAction['horario_id'] ?? $action['horario_id'] ?? null,
                'undo_count' => count($this->undoStack),
                'redo_count' => count($this->redoStack),
            ]);
        } catch (RuntimeException $e) {
            $this->redoStack[] = $action;
            $this->emit('alert', $e->getMessage(), 'Advertencia!', 'warning');
        } catch (\Throwable $e) {
            $this->redoStack[] = $action;
            Log::error('[HorarioRedo] Error', [
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->emit('alert', 'No se pudo rehacer el cambio.', 'Error!', 'error');
        }
    }

    private function pushUndoAction(array $action): void
    {
        $this->undoStack[] = $action;
        $this->redoStack = [];

        if (count($this->undoStack) > 50) {
            array_shift($this->undoStack);
        }

        Log::info('[HorarioUndo] Acción registrada', [
            'type' => $action['type'] ?? null,
            'horario_id' => $action['horario_id'] ?? null,
            'undo_count' => count($this->undoStack),
            'redo_count' => count($this->redoStack),
        ]);
    }

    private function snapshotHorario(Horario $horario): array
    {
        return [
            'horarios_dia' => $horario->horarios_dia instanceof Carbon
                ? $horario->horarios_dia->toDateString()
                : (string) $horario->horarios_dia,
            'horas_id' => $horario->horas_id,
            'grupo_id' => $horario->grupo_id,
            'profesores_id' => $horario->profesores_id,
            'espacios_id' => $horario->espacios_id,
            'origen' => $horario->origen ?? null,
            'protegido' => (bool) ($horario->protegido ?? false),
            'protegido_at' => $horario->protegido_at instanceof Carbon
                ? $horario->protegido_at->toDateTimeString()
                : ($horario->protegido_at ?? null),
        ];
    }

    private function applyHorarioSnapshot(Horario $horario, array $snapshot): void
    {
        $horario->horarios_dia = $snapshot['horarios_dia'] ?? $horario->horarios_dia;
        $horario->horas_id = $snapshot['horas_id'] ?? $horario->horas_id;
        $horario->grupo_id = $snapshot['grupo_id'] ?? $horario->grupo_id;
        $horario->profesores_id = $snapshot['profesores_id'] ?? $horario->profesores_id;
        $horario->espacios_id = $snapshot['espacios_id'] ?? $horario->espacios_id;

        if (array_key_exists('origen', $snapshot)) {
            $horario->origen = $snapshot['origen'];
        }

        if (array_key_exists('protegido', $snapshot)) {
            $horario->protegido = (bool) $snapshot['protegido'];
        }

        if (array_key_exists('protegido_at', $snapshot)) {
            $horario->protegido_at = $snapshot['protegido_at'];
        }

        $horario->save();
    }

    private function horarioActualCoincideConSnapshot(Horario $horario, array $snapshot): bool
    {
        $snapshotEspacio = $snapshot['espacios_id'] ?? null;

        return (string) Carbon::parse($horario->horarios_dia)->toDateString() === (string) Carbon::parse($snapshot['horarios_dia'] ?? now())->toDateString()
            && (int) $horario->horas_id === (int) ($snapshot['horas_id'] ?? 0)
            && (int) $horario->grupo_id === (int) ($snapshot['grupo_id'] ?? 0)
            && (int) $horario->profesores_id === (int) ($snapshot['profesores_id'] ?? 0)
            && ((is_null($horario->espacios_id) && is_null($snapshotEspacio)) || (int) $horario->espacios_id === (int) $snapshotEspacio);
    }

    private function horarioTieneDependencias(Horario $horario): bool
    {
        $horarioId = $horario->horarios_id;

        return Evaluacion::where('horarios_id', $horarioId)->exists()
            || Diario::where('horarios_id', $horarioId)->exists()
            || Plan::where('horarios_id', $horarioId)->exists()
            || ClasePrueba::where('horarios_id', $horarioId)->exists()
            || Prospecto::where('horarios_id', $horarioId)->exists();
    }

    private function existeHorarioManualProtegidoEnSnapshot(array $snapshot, ?int $exceptHorarioId = null): bool
    {
        $query = Horario::whereDate('horarios_dia', Carbon::parse($snapshot['horarios_dia'])->toDateString())
            ->where('horas_id', $snapshot['horas_id'])
            ->where('profesores_id', $snapshot['profesores_id'])
            ->where(function ($query) {
                $query->where('origen', 'manual')
                    ->orWhere('protegido', true);
            });

        if (array_key_exists('espacios_id', $snapshot) && is_null($snapshot['espacios_id'])) {
            $query->whereNull('espacios_id');
        } else {
            $query->where('espacios_id', $snapshot['espacios_id'] ?? null);
        }

        if ($exceptHorarioId) {
            $query->where('horarios_id', '!=', $exceptHorarioId);
        }

        return $query->exists();
    }

    private function applyHorarioAction(array $action, string $direction): ?array
    {
        return DB::transaction(function () use ($action, $direction) {
            $type = $action['type'] ?? null;
            $horarioId = $action['horario_id'] ?? null;

            if ($type === 'move') {
                $horario = Horario::find($horarioId);

                if (! $horario) {
                    throw new RuntimeException('No se puede aplicar la acción porque el horario ya no existe.');
                }

                $expected = $direction === 'undo' ? ($action['after'] ?? []) : ($action['before'] ?? []);
                $target = $direction === 'undo' ? ($action['before'] ?? []) : ($action['after'] ?? []);

                if ($this->horarioTieneDependencias($horario)) {
                    Log::warning($direction === 'undo' ? '[HorarioUndo] No se puede deshacer por dependencias' : '[HorarioRedo] No se puede rehacer por dependencias', [
                        'horario_id' => $horario->horarios_id,
                        'type' => $type,
                    ]);

                    throw new RuntimeException($direction === 'undo'
                        ? 'No se puede deshacer esta acción porque el horario ya tiene información asociada.'
                        : 'No se puede rehacer esta acción porque el horario ya tiene información asociada.');
                }

                if (! $this->horarioActualCoincideConSnapshot($horario, $expected)) {
                    Log::warning($direction === 'undo' ? '[HorarioUndo] Estado actual no coincide con historial' : '[HorarioRedo] Estado actual no coincide con historial', [
                        'horario_id' => $horario->horarios_id,
                        'actual' => $this->snapshotHorario($horario),
                        'expected' => $expected,
                    ]);

                    throw new RuntimeException($direction === 'undo'
                        ? 'No se puede deshacer porque este horario fue modificado después de la acción original.'
                        : 'No se puede rehacer porque este horario fue modificado después de la acción original.');
                }

                if ($this->existeHorarioManualProtegidoEnSnapshot($target, (int) $horario->horarios_id)) {
                    Log::warning($direction === 'undo' ? '[HorarioUndo] Acción bloqueada por horario manual protegido' : '[HorarioRedo] Acción bloqueada por horario manual protegido', [
                        'horario_id' => $horario->horarios_id,
                        'target' => $target,
                    ]);

                    throw new RuntimeException($direction === 'undo'
                        ? 'No se puede deshacer porque la posición de destino tiene una clase manual protegida.'
                        : 'No se puede rehacer porque la posición de destino tiene una clase manual protegida.');
                }

                $this->applyHorarioSnapshot($horario, $target);

                Log::info($direction === 'undo' ? '[HorarioUndo] Movimiento revertido' : '[HorarioRedo] Movimiento reaplicado', [
                    'horario_id' => $horario->horarios_id,
                    'direction' => $direction,
                    'target' => $target,
                ]);

                return $action;
            }

            if (in_array($type, ['create', 'manual_create'], true)) {
                if ($direction === 'undo') {
                    $horario = Horario::find($horarioId);

                    if (! $horario) {
                        throw new RuntimeException('No se puede deshacer porque el horario creado ya no existe.');
                    }

                    if (! $this->horarioActualCoincideConSnapshot($horario, $action['after'] ?? [])) {
                        Log::warning('[HorarioUndo] Estado actual no coincide con historial', [
                            'horario_id' => $horario->horarios_id,
                            'actual' => $this->snapshotHorario($horario),
                            'expected' => $action['after'] ?? [],
                        ]);

                        throw new RuntimeException('No se puede deshacer porque este horario fue modificado después de la acción original.');
                    }

                    if ($this->horarioTieneDependencias($horario)) {
                        Log::warning('[HorarioUndo] No se puede deshacer por dependencias', [
                            'horario_id' => $horario->horarios_id,
                            'type' => $type,
                        ]);

                        throw new RuntimeException('No se puede deshacer esta acción porque el horario ya tiene información asociada.');
                    }

                    $horario->delete();

                    Log::info('[HorarioUndo] Horario creado eliminado', [
                        'horario_id' => $horarioId,
                        'type' => $type,
                    ]);

                    return $action;
                }

                $payload = $action['after'] ?? [];
                unset($payload['horarios_id']);

                if ($type === 'manual_create') {
                    $payload['origen'] = 'manual';
                    $payload['protegido'] = true;
                    $payload['protegido_at'] = $payload['protegido_at'] ?? now();
                } else {
                    $payload['origen'] = $payload['origen'] ?? 'drag_drop';
                    $payload['protegido'] = (bool) ($payload['protegido'] ?? false);
                }

                if ($this->existeHorarioManualProtegidoEnSnapshot($payload)) {
                    Log::warning('[HorarioRedo] Acción bloqueada por horario manual protegido', [
                        'type' => $type,
                        'target' => $payload,
                    ]);

                    throw new RuntimeException('No se puede rehacer porque la posición de destino tiene una clase manual protegida.');
                }

                $horario = Horario::create($payload);
                $action['horario_id'] = $horario->horarios_id;
                $action['after'] = $this->snapshotHorario($horario);

                Log::info('[HorarioRedo] Horario recreado', [
                    'horario_id' => $horario->horarios_id,
                    'type' => $type,
                ]);

                return $action;
            }

            throw new RuntimeException('No se reconoce la acción de historial del horario.');
        });
    }

    public function mount($modalidad){
        $this->modalidad = $modalidad;
        $this->estudiantes = collect([]);
        $this->arr_capitulos = collect([]);
        $this->arr_tematicas = collect([]);
        $this->arr_niveles = Nivel::all()->pluck('nivel_descripcion','nivel_id');
        $this->espacios = Espacio::all();

    }

    public function updatedYdiario($value)
    {
        // Actualiza la fecha cuando ydiario cambia
        $this->fecha = Carbon::parse($value);
    }


    public function render()
    {
        $activeWeek = ActiveWeek::where('start_date', $this->inicio)->first();
        if ($activeWeek && $activeWeek->end_date !== $this->fin) {
            $activeWeek->update(['end_date' => $this->fin]);
        }
        $this->semana_activa = (bool) ($activeWeek?->is_active);

        $espacios = Espacio::all();
        $horas_semana = Hora::where('tipo',1)->orderBy('horas_id', 'asc')->get(); // Assuming 'tipo' 1 for Mon-Fri
        $horas_fds = Hora::where('tipo',2)->orderBy('horas_id', 'asc')->get();
        $array_horario = array();

        // 1. Fetch all professor blocks (can be optimized by date range if performance is an issue)
        $todosLosBloqueos = BloqueosProfesores::all();

        // 2. Fetch existing Horario records for the week
        $horariosCollection = Horario::where('horarios_dia','>=', $this->inicio)
            ->where('horarios_dia','<=', $this->fin)
            ->with(['grupo.modalidad', 'profesor', 'espacio', 'diario']) // Eager load relations
            ->orderBy('horarios_dia', 'asc')
            ->orderBy('horas_id', 'asc')
            ->orderBy('profesores_id', 'asc')
            ->get()
            ->keyBy(function ($item) { // Key by composite key for easy lookup
                return $item->horarios_dia . '_' . $item->horas_id . '_' . $item->profesores_id;
            });
        $pendientesAnteriores = [];
        $horariosSemana = $horariosCollection->values();

        if ($horariosSemana->isNotEmpty()) {
            $grupoIds = $horariosSemana->pluck('grupo_id')->unique()->values();
            $horariosPreviosPorGrupo = Horario::whereIn('grupo_id', $grupoIds)
                ->whereDate('horarios_dia', '<', $this->inicio)
                ->with('diario')
                ->orderBy('horarios_dia', 'desc')
                ->orderBy('horas_id', 'desc')
                ->get()
                ->groupBy('grupo_id')
                ->map->first();

            $horariosPorGrupo = $horariosSemana->groupBy('grupo_id');
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

        $profesoresQuery = Profesor::query()
            ->where('profesores_activo', 1);

        if ((int) $this->modalidad === 1) {
            $profesoresQuery->where('modalidad_id', 1);
        } else {
            $profesoresQuery->where('modalidad_id', (int) $this->modalidad);
        }

        $profesores = $profesoresQuery->get();

        // Iterate through all displayable slots
        $currentIterDay = Carbon::parse($this->inicio);
        while ($currentIterDay->lte(Carbon::parse($this->fin))) {
            $fechaStr = $currentIterDay->toDateString();
            $diaDeSemanaIso = $currentIterDay->dayOfWeekIso; // 1 (Mon) to 7 (Sun)

            $horasParaIterar = ($diaDeSemanaIso >= 1 && $diaDeSemanaIso <= 5) ? $horas_semana : $horas_fds;

            foreach ($horasParaIterar as $hora) {
                foreach ($profesores as $profesor) {
                    $slotKey = $fechaStr . '_' . $hora->horas_id . '_' . $profesor->profesores_id;
                    $isBlockedForThisSlot = false;

                    // Check for blocks
                    foreach ($todosLosBloqueos as $bloqueo) {
                        if ($bloqueo->profesor_id == $profesor->profesores_id) {
                            if ($bloqueo->fecha && Carbon::parse($bloqueo->fecha)->isSameDay($currentIterDay)) {
                                $isBlockedForThisSlot = true; // Full day block
                                break;
                            }
                            if (!$bloqueo->fecha && $bloqueo->dias_id == $diaDeSemanaIso && $bloqueo->horas_id == $hora->horas_id) {
                                $isBlockedForThisSlot = true; // Recurring block
                                break;
                            }
                        }
                    }

                    if ($isBlockedForThisSlot) {
                        $array_horario[$fechaStr][$hora->horas_id][$profesor->profesores_id] = [
                            'nombre' => 'BLOQUEADO',
                            'color' => $profesor->profesores_color,
                            'espacios_id' => null,
                            'grupo_id' => null,
                            'espacio' => 'N/A',
                            'enlace' => null,
                            'modalidad' => null,
                            'bgcolor' => 'bg-gray-200', // Grey for blocked
                            'id' => 'blocked-' . $profesor->profesores_id . '-' . $fechaStr . '-' . $hora->horas_id,
                            'is_blocked' => true,
                            'is_assigned' => false,
                        ];
                    } elseif ($horariosCollection->has($slotKey)) {
                        $horario = $horariosCollection->get($slotKey);
                        $bgColor = ($horario->grupo && $horario->grupo->modalidad_id == 1) ? 'bg-red-100' : 'bg-green-100';
                        $groupName = $horario->grupo ? $horario->grupo->grupo_nombre : 'Error: Grupo no cargado';

                        $array_horario[$fechaStr][$hora->horas_id][$profesor->profesores_id] = [
                            'nombre' => $groupName,
                            'color' => $horario->profesor->profesores_color,
                            'espacios_id' => $horario->espacios_id,
                            'grupo_id' => $horario->grupo_id,
                            'espacio' => $horario->espacio ? $horario->espacio->espacios_nombre : 'N/A',
                            'enlace' => $horario->espacio ? $horario->espacio->espacios_enlace : null,
                            'modalidad' => $horario->espacio ? $horario->espacio->modalidad_id : null,
                            'bgcolor' => $bgColor,
                            'id' => $horario->horarios_id,
                            'origen' => $horario->origen,
                            'protegido' => (bool) $horario->protegido,
                            'diario_actualizado' => $horario->diario?->updated_at,
                            'diario_anterior_pendiente' => $pendientesAnteriores[$horario->horarios_id] ?? false,
                            'is_blocked' => false,
                            'is_assigned' => true,
                        ];
                    }
                }
            }
            $currentIterDay->addDay();
        }


        $clasesPruebaSemana = ClasePrueba::with(['prospecto', 'grupo'])
            ->whereBetween('horarios_dia', [$this->inicio, $this->fin])
            ->whereNull('deleted_at')
            ->where('estado', '!=', 'cancelada')
            ->get();

        $clasesPruebaPorSlot = [];
        foreach ($clasesPruebaSemana as $clasePrueba) {
            $profesorSlot = $clasePrueba->profesores_id ?: 0;
            $key = $clasePrueba->horarios_dia->toDateString() . '_' . $clasePrueba->horas_id . '_' . $profesorSlot;
            $clasesPruebaPorSlot[$key][] = $clasePrueba;
        }

        $this->ocupados=array();
        $grupo_deta=$this->cargaDetalleGrupo($this->modalidad);
        $grupos = Grupo::where('estado_id', 1)
            ->orderBy('modalidad_id')
            ->orderBy('grupo_nombre')
            ->get();
        $profesorIds = $profesores->pluck('profesores_id');

        $clasesPorProfesor = $profesorIds->isNotEmpty()
            ? Horario::whereBetween('horarios_dia', [$this->inicio, $this->fin])
                ->whereIn('profesores_id', $profesorIds)
                ->select('profesores_id', DB::raw('count(*) as total'))
                ->groupBy('profesores_id')
                ->pluck('total', 'profesores_id')
            : collect();
        $dias = Dia::take(5)->get();
        $dias2 = Dia::offset(5)->limit(5)->get();

        $prospectosClasePrueba = Prospecto::where(function ($query) {
                $query->whereNull('grupo_id')
                    ->orWhereDoesntHave('inscripciones');
            })
            ->orderBy('prospectos_nombres')
            ->get();
        $horariosParaLog = collect($array_horario)
            ->flatten(3)
            ->filter(fn ($item) => is_array($item) && isset($item['id']) && ! str_starts_with((string) $item['id'], 'blocked-'));
        $conteoOrigenes = $horariosParaLog
            ->groupBy(fn ($item) => $item['origen'] ?? 'sin_origen')
            ->map->count()
            ->toArray();
        $conteoProtegidos = $horariosParaLog
            ->filter(fn ($item) => ! empty($item['protegido']))
            ->count();
        $horariosVisiblesComoManualPorOrigen = $horariosParaLog
            ->filter(fn ($item) => ($item['origen'] ?? null) === 'manual')
            ->count();
        $horariosVisiblesComoManualPorProtegido = $horariosParaLog
            ->filter(fn ($item) => ($item['origen'] ?? null) !== 'manual' && ! empty($item['protegido']))
            ->count();

        $this->logHorarioDebug('render:resumen_origenes', [
            'conteo_origenes' => $conteoOrigenes,
            'conteo_protegidos' => $conteoProtegidos,
        ]);
        $this->logHorarioDebug('render:muestra_horarios', [
            'sample' => $horariosParaLog
                ->take(10)
                ->map(fn ($item) => [
                    'horarios_id' => $item['id'] ?? null,
                    'grupo_id' => $item['grupo_id'] ?? null,
                    'origen' => $item['origen'] ?? null,
                    'protegido' => $item['protegido'] ?? null,
                    'manual_visual' => (($item['origen'] ?? null) === 'manual') || (($item['protegido'] ?? false) === true),
                ])
                ->values()
                ->toArray(),
        ]);
        $this->logHorarioDebug('render:manual_visual_diagnostico', [
            'manual_por_origen' => $horariosVisiblesComoManualPorOrigen,
            'manual_por_protegido_sin_origen_manual' => $horariosVisiblesComoManualPorProtegido,
            'diagnostico' => $horariosVisiblesComoManualPorProtegido > 0
                ? 'La vista probablemente está mostrando Manual por protegido=true aunque origen no sea manual.'
                : 'No se detectan horarios protegidos no manuales en esta muestra.',
        ]);
        $this->logHorarioDebug('render:manual_badge_rule', [
            'rule' => 'Manual badge should depend only on origen=manual, not protegido=true',
        ]);

        // $this->porcentaje = 100 / (count($horas) * count($dias));
        return view('livewire.show-horarios',[
                                            'espacios'=>$espacios
                                           ,'horas'=>$horas_semana // Pass the correct hour sets
                                           ,'horas2'=>$horas_fds
                                           ,'horarios'=>$array_horario
                                           ,'grupos'=>$grupos
                                           ,'grupo_deta'=>$grupo_deta
                                           ,'profesores'=>$profesores
                                           ,'clasesPorProfesor'=>$clasesPorProfesor
                                           ,'dias'=>$dias
                                           ,'dias2'=>$dias2
                                           ,'fecha'=>$this->fecha
                                           ,'clasesPruebaPorSlot' => $clasesPruebaPorSlot
                                           ,'prospectosClasePrueba' => $prospectosClasePrueba
                                            ]);
    }

    public function edit($horarios_dia,$espacios_id,$horas_id,$profesores_id,$grupo_id=''){
        $this->horarios_dia = $horarios_dia;
        $this->espacios_id = $espacios_id;
        $this->id_espacios = $espacios_id ?: null;
        $this->horas_id = $horas_id;
        $this->grupo_id = $grupo_id;
        $this->profesores_id = $profesores_id;
        $this->open_edit_diario = false;
        $this->open_edit_plan = false;
        $this->open_create_clase_prueba = false;
        $this->open_edit = true;
    }

    public function anterior(){
        $this->fecha = $this->fecha->subWeek();
        $this->ydiario = $this->fecha->isoFormat('Y-MM-DD');
        $this->year = $this->fecha->isoWeekYear;
        $this->semana = $this->fecha->weekOfYear;
        $this->inicio = $this->fecha->startOfWeek()->toDateString();
        $this->fin = $this->fecha->endOfWeek()->toDateString();
    }

    public function siguiente(){
        $this->fecha = $this->fecha->addWeek();
        $this->ydiario = $this->fecha->isoFormat('Y-MM-DD');
        $this->year = $this->fecha->isoWeekYear;
        $this->semana = $this->fecha->weekOfYear;
        $this->inicio = $this->fecha->startOfWeek()->toDateString();
        $this->fin = $this->fecha->endOfWeek()->toDateString();
    }

    public function toggleSemanaActiva(): void
    {
        $activeWeek = ActiveWeek::firstOrCreate(
            ['start_date' => $this->inicio],
            [
                'end_date' => $this->fin,
                'is_active' => true,
            ]
        );

        if (!$activeWeek->wasRecentlyCreated) {
            $activeWeek->is_active = !$activeWeek->is_active;
            $activeWeek->end_date = $this->fin;
            $activeWeek->save();
        }

        $this->semana_activa = $activeWeek->is_active;

        $message = $this->semana_activa ? 'La semana ha sido activada' : 'La semana ha sido desactivada';
        $this->emit('alert', $message);
    }

    private function normalizeEspacioId($espaciosId): ?int
    {
        if (blank($espaciosId)) {
            return $this->sinAsignarEspacioId();
        }

        $espaciosId = (int) $espaciosId;

        if (Espacio::whereKey($espaciosId)->exists()) {
            return $espaciosId;
        }

        return $this->sinAsignarEspacioId();
    }

    private function sinAsignarEspacioId(): ?int
    {
        return Espacio::where('espacios_nombre', 'Sin asignar')
            ->orWhere('espacios_descripcion', 'Sin asignar')
            ->orderBy('espacios_id')
            ->value('espacios_id');
    }

    public function save(){
        $validated = $this->validate([
            'grupo_id'=>'required',
        ]);
        if (! $this->puedeAsignarGrupoDesdeFecha((int) $this->grupo_id, $this->horarios_dia)) {
            $this->emit('alert', 'No se puede asignar este grupo a un horario anterior a su fecha de inicio.', 'Error!', 'error');
            return;
        }
        // Check for blocks BEFORE creating
        $carbonFecha = Carbon::parse($this->horarios_dia);
        $isBlocked = BloqueosProfesores::isBlocked($this->profesores_id, $carbonFecha, $this->horas_id)->exists();

        if ($isBlocked) {
            $this->emit('alert', 'Este horario está bloqueado para el profesor seleccionado.', 'Error!', 'error');
            return;
        }

        // Check if professor is already scheduled
        $isOccupied = Horario::where('profesores_id', $this->profesores_id)
            ->where('horarios_dia', $this->horarios_dia)
            ->where('horas_id', $this->horas_id)
            ->exists();

        if ($isOccupied) {
            $this->emit('alert', 'El profesor ya tiene un horario asignado en esta fecha y hora.', 'Error!', 'error');
            return;
        }



        $protegidoAtManual = now();
        $payloadCreateHorario = [
            'horarios_dia' => $this->horarios_dia,
            'espacios_id' => $this->normalizeEspacioId($this->id_espacios),
            'horas_id' => $this->horas_id,
            'grupo_id' => $this->grupo_id,
            'profesores_id' => $this->profesores_id,
            'origen' => 'manual',
            'protegido' => true,
            'protegido_at' => $protegidoAtManual,
        ];

        $this->logHorarioDebug('save_manual:before_create', [
            'payload' => $payloadCreateHorario,
        ]);
        Log::info('[HorariosManualDebug] asignacion_manual_detectada', [
            'file_context' => 'ShowHorarios::save',
            'motivo_esperado' => 'Solo debe ejecutarse en creación manual desde modal',
            'payload' => $payloadCreateHorario,
        ]);

        $horario = Horario::create($payloadCreateHorario);

        $this->pushUndoAction([
            'type' => 'manual_create',
            'horario_id' => $horario->horarios_id,
            'after' => $this->snapshotHorario($horario),
        ]);

        $this->logHorarioDebug('save_manual:after_create', [
            'horario_id' => $horario->horarios_id ?? null,
            'origen' => $horario->origen ?? null,
            'protegido' => $horario->protegido ?? null,
            'protegido_at' => $horario->protegido_at ?? null,
        ]);

        Log::info('[HorariosProtegidos] Clase manual creada', [
            'horarios_id' => $horario->horarios_id ?? null,
            'grupo_id' => $horario->grupo_id ?? null,
            'horarios_dia' => $horario->horarios_dia ?? null,
            'horas_id' => $horario->horas_id ?? null,
            'origen' => $horario->origen ?? null,
            'protegido' => $horario->protegido ?? null,
        ]);
        $this->enlazarClasesPruebaPendientes($horario);

        $this->reset(['open_edit','horarios_dia','espacios_id','id_espacios','grupo_id',
        'horas_id','profesores_id']);
        $this->emitTo('show-horarios','render');
        $this->emit('alert','El horario fue agregado satifactoriamente');
    }

    public function openCreateClasePrueba($fecha, $horaId, $profesorId = null, $grupoId = null): void
    {
        $this->clase_prueba_horarios_dia = $fecha;
        $this->clase_prueba_horas_id = $horaId;
        $this->clase_prueba_profesores_id = $profesorId ?: null;
        $this->clase_prueba_grupo_id = $grupoId ?: null;
        $this->clase_prueba_prospectos_ids = [];
        $this->clase_prueba_modalidad_id = $grupoId
            ? Grupo::where('grupo_id', $grupoId)->value('modalidad_id')
            : null;
        $this->open_edit_diario = false;
        $this->open_edit_plan = false;
        $this->open_edit = false;
        $this->open_create_clase_prueba = true;
    }

    public function updatedClasePruebaGrupoId($grupoId): void
    {
        if (! $grupoId) {
            $this->clase_prueba_modalidad_id = null;
            return;
        }

        $this->clase_prueba_modalidad_id = Grupo::where('grupo_id', $grupoId)->value('modalidad_id');
    }

    public function saveClasePrueba(): void
    {
        $this->clase_prueba_profesores_id = $this->clase_prueba_profesores_id ?: null;
        $this->clase_prueba_espacios_id = $this->normalizeEspacioId($this->clase_prueba_espacios_id);
        $this->clase_prueba_modalidad_id = $this->clase_prueba_modalidad_id ?: null;

        $this->clase_prueba_prospectos_ids = collect($this->clase_prueba_prospectos_ids)
            ->when(! is_array($this->clase_prueba_prospectos_ids), fn ($c) => collect((array) $this->clase_prueba_prospectos_ids))
            ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->validate([
            'clase_prueba_prospectos_ids' => 'required|array|min:1',
            'clase_prueba_prospectos_ids.*' => 'required|exists:prospectos,prospectos_id',
            'clase_prueba_grupo_id' => 'required|exists:grupos,grupo_id',
            'clase_prueba_horarios_dia' => 'required|date',
            'clase_prueba_horas_id' => 'required|exists:horas,horas_id',
            'clase_prueba_profesores_id' => 'nullable|exists:profesores,profesores_id',
            'clase_prueba_espacios_id' => 'nullable|exists:espacios,espacios_id',
            'clase_prueba_modalidad_id' => 'nullable|exists:modalidades,modalidad_id',
            'clase_prueba_observacion' => 'nullable|string|max:255',
        ]);

        $prospectosIds = collect($this->clase_prueba_prospectos_ids)->filter()->unique()->values();
        $duplicadosCount = ClasePrueba::whereIn('prospectos_id', $prospectosIds)
            ->where('grupo_id', $this->clase_prueba_grupo_id)
            ->whereDate('horarios_dia', $this->clase_prueba_horarios_dia)
            ->where('horas_id', $this->clase_prueba_horas_id)
            ->where('estado', '!=', 'cancelada')
            ->count();
        if ($duplicadosCount > 0) {
            $this->emit('alert', 'Uno o más prospectos ya tienen clase de prueba en el mismo grupo/fecha/hora.', 'Advertencias!', 'warning');
            return;
        }

        $horario = Horario::where('grupo_id', $this->clase_prueba_grupo_id)
            ->whereDate('horarios_dia', $this->clase_prueba_horarios_dia)
            ->where('horas_id', $this->clase_prueba_horas_id)
            ->first();

        Log::info('Programando clase de prueba', [
            'prospectos_ids' => $prospectosIds->all(),
            'grupo_id' => $this->clase_prueba_grupo_id,
            'horarios_id' => $horario?->horarios_id,
            'horarios_dia' => $this->clase_prueba_horarios_dia,
            'horas_id' => $this->clase_prueba_horas_id,
        ]);

        foreach ($prospectosIds as $prospectoId) {
            ClasePrueba::create([
                'prospectos_id' => $prospectoId,
                'grupo_id' => $this->clase_prueba_grupo_id,
                'horarios_id' => $horario?->horarios_id,
                'horarios_dia' => $this->clase_prueba_horarios_dia,
                'horas_id' => $this->clase_prueba_horas_id,
                'profesores_id' => $this->clase_prueba_profesores_id,
                'espacios_id' => $this->clase_prueba_espacios_id,
                'modalidad_id' => $this->clase_prueba_modalidad_id,
                'observacion' => $this->clase_prueba_observacion,
            ]);
        }

        $this->reset(['open_create_clase_prueba', 'clase_prueba_prospectos_ids', 'clase_prueba_grupo_id', 'clase_prueba_horarios_dia', 'clase_prueba_horas_id', 'clase_prueba_profesores_id', 'clase_prueba_espacios_id', 'clase_prueba_modalidad_id', 'clase_prueba_observacion']);
        $this->emit('alert', 'Clase de prueba programada satisfactoriamente');
    }

    private function puedeAsignarGrupoDesdeFecha(int $grupoId, string $fecha): bool
    {
        if ($grupoId === 0) {
            return false;
        }

        $grupo = Grupo::select('fecha_inicio', 'es_evento')->where('grupo_id', $grupoId)->first();
        if (! $grupo || $grupo->es_evento || empty($grupo->fecha_inicio)) {
            return true;
        }

        return Carbon::parse($fecha)->toDateString() >= Carbon::parse($grupo->fecha_inicio)->toDateString();
    }

    public function delete(Horario $horario){
        // Verificar si el horario tiene evaluaciones asociadas
        $evaluaciones = Evaluacion::where('horarios_id', $horario->horarios_id)->exists();

        $this->logHorarioDebug('delete:start', [
            'horario_id' => $horario->horarios_id ?? null,
            'grupo_id' => $horario->grupo_id ?? null,
            'origen' => $horario->origen ?? null,
            'protegido' => $horario->protegido ?? null,
            'has_evaluaciones' => $evaluaciones,
        ]);

        if ($evaluaciones) {
            $this->emit('alert', 'No se puede eliminar el horario porque tiene evaluaciones asociadas', 'Advertencias!', 'warning');
            return;
        }

        if ($horario->origen === 'manual') {
            $this->logHorarioDebug('delete:blocked_manual', [
                'horario_id' => $horario->horarios_id ?? null,
                'origen' => $horario->origen ?? null,
                'protegido' => $horario->protegido ?? null,
            ]);

            Log::info('[HorariosProtegidos] Intento de eliminar clase manual bloqueado', [
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

        $this->logHorarioDebug('delete:executed', [
            'horario_id' => $horario->horarios_id ?? null,
            'origen' => $horario->origen ?? null,
            'protegido' => $horario->protegido ?? null,
        ]);

        $horario->delete();
        $this->emit('alert', 'El horario fue eliminado satisfactoriamente');
    }

    public function deactivateGrupo(int $grupoId, string $fecha, int $horaId): void
    {
        $fechaFormateada = Carbon::parse($fecha)->toDateString();
        $modalidadId = $this->modalidad ?? Grupo::where('grupo_id', $grupoId)->value('modalidad_id');

        $grupoInactivo = GrupoInactivo::where('grupo_id', $grupoId)
            ->where('fecha', $fechaFormateada)
            ->where('horas_id', $horaId)
            ->where(function ($query) use ($modalidadId) {
                $query->where('modalidad_id', $modalidadId)
                    ->orWhereNull('modalidad_id');
            })
            ->first();

        if ($grupoInactivo) {
            $this->emit('alert', 'El grupo ya está desactivado para esta fecha y hora', 'Advertencias!', 'warning');
            return;
        }

        GrupoInactivo::create([
            'grupo_id' => $grupoId,
            'fecha' => $fechaFormateada,
            'horas_id' => $horaId,
            'modalidad_id' => $modalidadId,
        ]);

        Log::info('[HorariosProtegidos] Grupo inactivo aplicado solo a horarios base', [
            'grupo_id' => $grupoId,
            'fecha' => $fechaFormateada,
            'horas_id' => $horaId,
            'modalidad_id' => $modalidadId,
        ]);

        $this->emit('alert', 'El grupo fue desactivado para la fecha y hora seleccionada');
        $this->emitTo('show-horarios', 'render');
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
            $horarioPlanData = $this->buildHorarioPlanData($h);
            
            if ($grouped->has($hId) && $grouped->get($hId)->isNotEmpty()) {
                // Si hay evaluaciones reales o clases de prueba, las convertimos a array
                // y garantizamos que cada item tenga los datos completos del horario/diario.
                $finalEvaluaciones[$hId] = $grouped->get($hId)->values()->map(function ($item) use ($horarioPlanData) {
                    $itemArray = $item->toArray();
                    $itemArray['horario'] = $horarioPlanData;

                    return $itemArray;
                })->toArray();
            } else {
                // Si no hay evaluaciones, inyectamos un elemento dummy con toda la info de la relación
                $finalEvaluaciones[$hId] = [
                    [
                        'is_dummy' => true,
                        'prospecto' => null,
                        'asistio' => null,
                        'observacion' => '',
                        'horario' => $horarioPlanData,
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
        $this->open_edit = false;
        $this->open_create_clase_prueba = false;
        $this->open_edit_plan = true;
    }

    private function buildHorarioPlanData(Horario $horario): array
    {
        return [
            'horarios_id' => $horario->horarios_id,
            'horarios_dia' => $horario->horarios_dia instanceof Carbon ? $horario->horarios_dia->toDateString() : $horario->horarios_dia,
            'profesor' => $horario->profesor ? [
                'profesores_nombres' => $horario->profesor->profesores_nombres ?? '',
                'profesores_apellidos' => $horario->profesor->profesores_apellidos ?? '',
            ] : null,
            'espacio' => $horario->espacio ? [
                'espacios_nombre' => $horario->espacio->espacios_nombre ?? 'N/A',
            ] : null,
            'diario' => $horario->diario ? [
                'diarios_id' => $horario->diario->diarios_id,
                'niveles_id' => $horario->diario->niveles_id,
                'capitulos_id' => $horario->diario->capitulos_id,
                'tematica_id' => $horario->diario->tematica_id,
                'numero_clases' => $horario->diario->numero_clases,
                'diarios_hecho' => $horario->diario->diarios_hecho,
                'diarios_porhacer' => $horario->diario->diarios_porhacer,
                'nivel' => $horario->diario->nivel ? [
                    'nivel_id' => $horario->diario->nivel->nivel_id,
                    'nivel_descripcion' => $horario->diario->nivel->nivel_descripcion,
                ] : null,
                'capitulo' => $horario->diario->capitulo ? [
                    'capitulo_id' => $horario->diario->capitulo->capitulo_id,
                    'capitulo_descripcion' => $horario->diario->capitulo->capitulo_descripcion,
                    'capitulo_codigo' => $horario->diario->capitulo->capitulo_codigo,
                ] : null,
                'tematica' => $horario->diario->relationLoaded('tematica') && $horario->diario->getRelation('tematica') ? [
                    'tematica_id' => $horario->diario->getRelation('tematica')->tematica_id,
                    'tematica_descripcion' => $horario->diario->getRelation('tematica')->tematica_descripcion,
                ] : null,
            ] : null,
        ];
    }


    public function editDiario($id){

        $this->diario = Diario::where('horarios_id',$id)->first();

        $horario = Horario::where('horarios_id',$id)->first();
        $this->diarios_profesor = $horario->profesor->profesores_nombres .' '.$horario->profesor->profesores_apellidos;
        $grupoNombre = $horario->grupo?->grupo_nombre ?? 'Sin grupo';
        $fechaClase = Carbon::parse($horario->horarios_dia)->format('d/m/Y');
        $horaDesde = $horario->hora?->horas_desde ? Carbon::parse($horario->hora->horas_desde)->format('H:i') : '--:--';
        $horaHasta = $horario->hora?->horas_hasta ? Carbon::parse($horario->hora->horas_hasta)->format('H:i') : '--:--';
        $this->diario_contexto = "Grupo {$grupoNombre} | {$fechaClase} | {$horaDesde} - {$horaHasta}";
        $this->espacios_id = $horario->espacios_id;

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
        $this->open_edit = false;
        $this->open_create_clase_prueba = false;
        $this->open_edit_diario = true;
    }


    public function saveDiario(){
        $validated = $this->validate($this->diarioValidationRules(), $this->diarioValidationMessages());

        try {
            DB::beginTransaction();
            $datosGeneralesValidados = (bool) ($this->validado_datos_generales && $this->idnivel && $this->id_capitulo);
            $numeroClases = $this->numero_clases === '' ? null : $this->numero_clases;
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
                'validado_prospectos' => (bool) $this->validado_prospectos
            ]);
            // Guardar el nivel y capítulo en la tabla de grupos
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

                // Update class details from the schedule
                if ($horarioActual) {
                    $clasePrueba->horarios_id = $horarioActual->horarios_id;
                    $clasePrueba->profesores_id = $horarioActual->profesores_id;
                    $clasePrueba->espacios_id = $this->normalizeEspacioId($this->espacios_id);
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

            $horario = Horario::find($this->diarios_horarios_id);
            $horario->espacios_id = $this->normalizeEspacioId($this->espacios_id);
            $horario->save();
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

    private function diarioValidationRules(): array
    {
        return [
            'diarios_hecho' => 'required|min:15|max:550',
            'diarios_porhacer' => 'required|min:15|max:550',
            'idnivel' => 'required',
            'id_capitulo' => 'required',
            'id_tematica' => 'required',
            'numero_clases' => 'nullable|numeric|min:0.5',
            'validado_datos_generales' => 'accepted',
            'validado_contenido_clase' => 'accepted',
            'validado_estudiantes' => 'accepted',
            'validado_prospectos' => count($this->clasesPrueba) ? 'accepted' : 'nullable',
        ];
    }

    private function diarioValidationMessages(): array
    {
        return [
            'validado_datos_generales.accepted' => 'Debes validar los datos generales antes de actualizar el diario.',
            'validado_contenido_clase.accepted' => 'Debes validar el contenido de la clase antes de actualizar el diario.',
            'validado_estudiantes.accepted' => 'Debes validar los estudiantes antes de actualizar el diario.',
            'validado_prospectos.accepted' => 'Debes validar las clases de prueba/prospectos antes de actualizar el diario.',
        ];
    }

    private function enlazarClasesPruebaPendientes(Horario $horario): void
    {
        $clases = ClasePrueba::whereNull('horarios_id')
            ->where(function ($query) use ($horario) {
                $query->where('grupo_id', $horario->grupo_id)
                      ->orWhere('profesores_id', $horario->profesores_id);
            })
            ->whereDate('horarios_dia', $horario->horarios_dia)
            ->where('horas_id', $horario->horas_id)
            ->get();
        foreach ($clases as $clase) {
            $clase->horarios_id = $horario->horarios_id;
            if (empty($clase->profesores_id)) {
                $clase->profesores_id = $horario->profesores_id;
            }
            if (empty($clase->espacios_id)) {
                $clase->espacios_id = $horario->espacios_id;
            }
            $clase->save();
            Log::info('Clase de prueba enlazada a horario oficial', [
                'clase_prueba_id' => $clase->clase_prueba_id,
                'prospectos_id' => $clase->prospectos_id,
                'grupo_id' => $clase->grupo_id,
                'horarios_id' => $clase->horarios_id,
                'horarios_dia' => $clase->horarios_dia?->toDateString(),
                'horas_id' => $clase->horas_id,
            ]);
        }
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

        $gruposInactivos = GrupoInactivo::whereBetween('fecha', [$this->inicio, $this->fin])
            ->get()
            ->groupBy('grupo_id');

       if($modalidad == 2){
           $detalles = DB::table('grupos_detalles')
                               ->join('grupos', 'grupos_detalles.grupo_id', '=', 'grupos.grupo_id')
                               ->where('grupos.modalidad_id', $modalidad)
                               ->where('grupos.estado_id', 1) // Solo grupos activos
                               ->select('grupos_detalles.*', 'grupos.modalidad_id', 'grupos.grupo_nombre', 'grupos.fecha_inicio') // Selecciona los campos que necesitas
                               ->orderBy('grupos_detalles.grupo_id', 'asc')
                               ->orderBy('grupos_detalles.dias_id', 'asc')
                               ->orderBy('grupos_detalles.horas_id', 'asc')
                               ->get();

            } else {
           $detalles = DB::table('grupos_detalles')
                               ->join('grupos', 'grupos_detalles.grupo_id', '=', 'grupos.grupo_id')
                               ->select('grupos_detalles.*', 'grupos.modalidad_id', 'grupos.grupo_nombre', 'grupos.fecha_inicio') // Selecciona los campos que necesitas
                               ->where('grupos.estado_id', 1) // Solo grupos activos
                               ->orderBy('grupos_detalles.grupo_id', 'asc')
                               ->orderBy('grupos_detalles.dias_id', 'asc')
                               ->orderBy('grupos_detalles.horas_id', 'asc')
                               ->get();
        }


        $cantidad=[];
        foreach ($detalles as $item) {
            // Fecha exacta del día de la semana del detalle
            $evaluar = Carbon::parse($this->fecha)->setISODate($this->year, $this->semana, $item->dias_id)->isoFormat('YYYY-MM-DD');

            if (!empty($item->fecha_inicio)) {
                $fechaEvaluar = Carbon::parse($evaluar)->toDateString();
                $fechaInicio = Carbon::parse($item->fecha_inicio)->toDateString();
                if (Carbon::parse($fechaEvaluar)->lt(Carbon::parse($fechaInicio))) {
                    continue;
                }
            }

            $inactivosGrupo = $gruposInactivos[$item->grupo_id] ?? collect();
            $estaInactivo = $inactivosGrupo->first(function ($inactivo) use ($evaluar, $modalidad, $item) {
                return $inactivo->fecha === $evaluar
                    && (int) $inactivo->horas_id === (int) $item->horas_id
                    && ($inactivo->modalidad_id === null || (int) $inactivo->modalidad_id === (int) $modalidad);
            });

            if ($estaInactivo) {
                Log::info('[HorariosProtegidos] Grupo inactivo ocultó horario base', [
                    'grupo_id' => $item->grupo_id,
                    'fecha' => $evaluar,
                    'horas_id' => $item->horas_id,
                    'modalidad_id' => $modalidad,
                ]);
                continue;
            }

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
                    // Incrementar para el siguiente grupo en esta hora y día
                    $cantidad[$evaluar][$item->horas_id]++;
                }
            }
        }


        return $grupo_deta;
    }

    public function updateGrupoHorario(
        $horarios_id,
        $horarios_dia,
        $horas_id,
        $grupo_id,
        $profesores_id,
        $espacios_id,
        $anterior_id,
        $anterior_dia = null,
        $anterior_hora = null,
        $anterior_profesor = null,
        $anterior_espacio = null
    )
    {
        $this->logHorarioDebug('updateGrupoHorario:start', [
            'params' => [
                'horarios_id' => $horarios_id ?? null,
                'anterior_id' => $anterior_id ?? null,
                'grupo_id' => $grupo_id ?? null,
                'profesores_id' => $profesores_id ?? null,
                'horarios_dia' => $horarios_dia ?? null,
                'horas_id' => $horas_id ?? null,
                'espacios_id' => $espacios_id ?? null,
                'modalidad_id' => $this->modalidad ?? null,
                'anterior_dia' => $anterior_dia ?? null,
                'anterior_hora' => $anterior_hora ?? null,
                'anterior_profesor' => $anterior_profesor ?? null,
                'anterior_espacio' => $anterior_espacio ?? null,
            ],
        ]);

        // --- INICIO: Validación de datos relacionados ---
        // Si se está moviendo un horario existente (no creando uno nuevo desde un grupo base)
        if ($anterior_id != '0') {
            if (Evaluacion::where('horarios_id', $anterior_id)->exists()) {
                $this->emit('alert', 'No se puede mover el horario porque ya tiene una clase asociada con evaluaciones.', 'Advertencia!', 'warning');
                return;
            }
        }
        // --- FIN: Validación de datos relacionados ---
        // dd($horarios_id, $horarios_dia, $horas_id, $grupo_id, $profesores_id, $espacios_id, $anterior_id);
        if ($grupo_id == '0') {
            $this->emit('alert', 'El horario está vacío, no se puede realizar esta operación', 'Advertencias!', 'warning');
            $this->emitTo('show-horarios','render');
            return;
        } elseif ($horarios_id != '0' && $horarios_id == $anterior_id) {
            $this->emit('alert', 'El horario es el mismo, no se puede realizar esta operación', 'Advertencias!', 'warning');
            $this->emitTo('show-horarios','render');
            return;
        } else {
            if (! $this->puedeAsignarGrupoDesdeFecha((int) $grupo_id, $horarios_dia)) {
                $this->emit('alert', 'No se puede asignar este grupo a un horario anterior a su fecha de inicio.', 'Error!', 'error');
                $this->emitTo('show-horarios','render');
                return;
            }
             // Check for blocks for the target professor, date, and hour
            $carbonFecha = Carbon::parse($horarios_dia);
            $isTargetSlotBlocked = BloqueosProfesores::isBlocked($profesores_id, $carbonFecha, $horas_id)->exists();

            if ($isTargetSlotBlocked) {
                $this->emit('alert', 'El horario de destino está bloqueado para el profesor.', 'Error!', 'error');
                $this->emitTo('show-horarios','render'); // Re-render to reflect current state
                return;
            }

            $id_espacios = $this->normalizeEspacioId($espacios_id);
            $fechaAnterior = $anterior_dia ? Carbon::parse($anterior_dia)->toDateString() : null;
            $horaAnterior = $anterior_hora ? (int) $anterior_hora : null;

            $horarioAnteriorDebug = ! empty($anterior_id) && $anterior_id !== '0'
                ? Horario::find($anterior_id)
                : null;
            $horarioDestino = ! empty($horarios_id) && $horarios_id !== '0'
                ? Horario::find($horarios_id)
                : null;

            $this->logHorarioDebug('updateGrupoHorario:horario_anterior', [
                'anterior_id' => $anterior_id ?? null,
                'horario_anterior_exists' => (bool) $horarioAnteriorDebug,
                'horario_anterior' => $this->horarioDebugSnapshot($horarioAnteriorDebug),
            ]);
            $this->logHorarioDebug('updateGrupoHorario:horario_destino', [
                'horarios_id_param' => $horarios_id ?? null,
                'horario_destino_exists' => (bool) $horarioDestino,
                'horario_destino' => $this->horarioDebugSnapshot($horarioDestino),
            ]);

            if ($horarioDestino && $horarioDestino->origen === 'manual' && (string) $horarioDestino->horarios_id !== (string) $anterior_id) {
                Log::info('[HorariosProtegidos] Drag and drop bloqueado: destino es clase manual', [
                    'destino_horarios_id' => $horarioDestino->horarios_id,
                    'destino_grupo_id' => $horarioDestino->grupo_id,
                    'destino_fecha' => $horarioDestino->horarios_dia,
                    'destino_hora_id' => $horarioDestino->horas_id,
                    'origen_destino' => $horarioDestino->origen,
                    'source_horarios_id' => $anterior_id,
                    'nuevo_grupo_id' => $grupo_id,
                ]);

                $this->emit('alert', 'No se puede mover el grupo sobre una clase creada manualmente.', 'Advertencias!', 'warning');
                $this->emitTo('show-horarios', 'render');
                return;
            }

            $queryManualDestino = Horario::whereDate('horarios_dia', Carbon::parse($horarios_dia)->toDateString())
                ->where('horas_id', $horas_id)
                ->where('profesores_id', $profesores_id)
                ->where('origen', 'manual');

            if (is_null($id_espacios)) {
                $queryManualDestino->whereNull('espacios_id');
            } else {
                $queryManualDestino->where('espacios_id', $id_espacios);
            }

            $manualDestino = $queryManualDestino->first();

            if ($manualDestino && (string) $manualDestino->horarios_id !== (string) $anterior_id) {
                Log::info('[HorariosProtegidos] Creación/movimiento bloqueado: existe clase manual en destino', [
                    'manual_horarios_id' => $manualDestino->horarios_id,
                    'manual_grupo_id' => $manualDestino->grupo_id,
                    'fecha' => $horarios_dia,
                    'hora_id' => $horas_id,
                    'profesor_id' => $profesores_id,
                    'espacio_id' => $id_espacios,
                    'source_horarios_id' => $anterior_id,
                ]);

                $this->emit('alert', 'No se puede colocar el grupo en esta celda porque ya existe una clase manual.', 'Advertencias!', 'warning');
                $this->emitTo('show-horarios', 'render');
                return;
            }
            // dd($id_espacios);
            $operacionRealizada = DB::transaction(function () use (
                $anterior_id,
                $horarios_dia,
                $horas_id,
                $grupo_id,
                $id_espacios,
                $profesores_id,
                $horarios_id,
                $fechaAnterior,
                $horaAnterior,
                $horarioDestino
            ) {
                $horarioAnterior = $anterior_id != '0' ? Horario::find($anterior_id) : null;
                $this->logHorarioDebug('updateGrupoHorario:origen_decision_before', [
                    'anterior_id' => $anterior_id ?? null,
                    'horarios_id' => $horarios_id ?? null,
                    'horario_anterior_origen' => $horarioAnterior->origen ?? null,
                    'horario_anterior_protegido' => $horarioAnterior->protegido ?? null,
                    'horario_destino_origen' => $horarioDestino->origen ?? null,
                    'horario_destino_protegido' => $horarioDestino->protegido ?? null,
                ]);
                $origenDragDrop = $horarioAnterior && $horarioAnterior->origen === 'manual'
                    ? 'manual'
                    : 'drag_drop';
                if ($origenDragDrop === 'manual') {
                    Log::warning('[HorariosManualDebug] POSIBLE_ERROR: updateGrupoHorario intenta asignar origen manual', [
                        'anterior_id' => $anterior_id ?? null,
                        'horarios_id' => $horarios_id ?? null,
                        'grupo_id' => $grupo_id ?? null,
                        'horario_anterior_origen' => $horarioAnterior->origen ?? null,
                    ]);
                }
                $protegidoDragDrop = $origenDragDrop === 'manual';
                $protegidoAt = $protegidoDragDrop ? ($horarioAnterior?->protegido_at ?? now()) : null;
                $this->logHorarioDebug('updateGrupoHorario:origen_decision_after', [
                    'origen_final' => $origenDragDrop ?? null,
                    'protegido_final' => $protegidoDragDrop ?? null,
                    'protegido_at_final' => $protegidoAt ?? null,
                    'motivo' => 'Registrar si viene de manual, drag_drop, programado o creación nueva',
                ]);

                if ($horarioAnterior) {
                    if ($horarioAnterior->protegido || $horarioAnterior->origen === 'manual') {
                        Log::info('[HorariosProtegidos] Horario protegido excluido de grupos_inactivos al mover por drag and drop', [
                            'horarios_id' => $horarioAnterior->horarios_id,
                            'grupo_id' => $horarioAnterior->grupo_id,
                            'horarios_dia' => $horarioAnterior->horarios_dia,
                            'horas_id' => $horarioAnterior->horas_id,
                            'origen' => $horarioAnterior->origen,
                            'protegido' => $horarioAnterior->protegido,
                        ]);
                    } else {
                        $this->registrarGrupoInactivoPorCambioHorario($horarioAnterior, $horarios_dia, (int) $horas_id);
                    }

                    $payloadUpdateHorario = [
                        'horarios_dia' => $horarios_dia,
                        'horas_id' => $horas_id,
                        'grupo_id' => $grupo_id,
                        'espacios_id' => $id_espacios,
                        'profesores_id' => $profesores_id,
                        'origen' => $origenDragDrop,
                        'protegido' => $protegidoDragDrop,
                        'protegido_at' => $protegidoAt,
                    ];
                    $beforeSnapshot = $this->snapshotHorario($horarioAnterior);
                    $this->logHorarioDebug('updateGrupoHorario:before_update', [
                        'horario_id' => $horarioAnterior->horarios_id ?? null,
                        'before' => $this->horarioDebugSnapshot($horarioAnterior),
                        'payload_update' => $payloadUpdateHorario,
                    ]);
                    $horarioAnterior->update($payloadUpdateHorario);
                    $horarioAnterior->refresh();
                    $afterSnapshot = $this->snapshotHorario($horarioAnterior);
                    $this->pushUndoAction([
                        'type' => 'move',
                        'horario_id' => $horarioAnterior->horarios_id,
                        'before' => $beforeSnapshot,
                        'after' => $afterSnapshot,
                    ]);
                    $this->logHorarioDebug('updateGrupoHorario:after_update', [
                        'horario_id' => $horarioAnterior->horarios_id ?? null,
                        'after' => $this->horarioDebugSnapshot($horarioAnterior),
                    ]);

                    Log::info('[HorariosProtegidos] Horario movido por drag and drop', [
                        'horarios_id' => $horarioAnterior->horarios_id,
                        'grupo_id' => $horarioAnterior->grupo_id,
                        'horarios_dia' => $horarioAnterior->horarios_dia,
                        'horas_id' => $horarioAnterior->horas_id,
                        'origen' => $horarioAnterior->origen,
                        'protegido' => $horarioAnterior->protegido,
                    ]);

                    return true;
                }

                if ($fechaAnterior && $horaAnterior && ($fechaAnterior !== Carbon::parse($horarios_dia)->toDateString() || $horaAnterior !== (int) $horas_id)) {
                    $modalidadId = $this->modalidad ?? Grupo::where('grupo_id', $grupo_id)->value('modalidad_id');
                    GrupoInactivo::firstOrCreate([
                        'grupo_id' => $grupo_id,
                        'fecha' => $fechaAnterior,
                        'horas_id' => $horaAnterior,
                        'modalidad_id' => $modalidadId,
                    ]);
                }

                if ($horarios_id != '0') {
                    $horario = Horario::find($horarios_id);
                    if ($horario) {
                        if ($horario->origen === 'manual' && (string) $horario->horarios_id !== (string) $anterior_id) {
                            $this->emit('alert', 'No se puede sobrescribir una clase creada manualmente.', 'Advertencias!', 'warning');
                            return false;
                        }

                        $payloadUpdateHorario = [
                            'horarios_dia' => $horarios_dia,
                            'horas_id' => $horas_id,
                            'grupo_id' => $grupo_id,
                            'espacios_id' => $id_espacios,
                            'profesores_id' => $profesores_id,
                            'origen' => 'drag_drop',
                            'protegido' => false,
                            'protegido_at' => null,
                        ];
                        $beforeSnapshot = $this->snapshotHorario($horario);
                        $this->logHorarioDebug('updateGrupoHorario:before_update', [
                            'horario_id' => $horario->horarios_id ?? null,
                            'before' => $this->horarioDebugSnapshot($horario),
                            'payload_update' => $payloadUpdateHorario,
                        ]);
                        $horario->update($payloadUpdateHorario);
                        $horario->refresh();
                        $afterSnapshot = $this->snapshotHorario($horario);
                        $this->pushUndoAction([
                            'type' => 'move',
                            'horario_id' => $horario->horarios_id,
                            'before' => $beforeSnapshot,
                            'after' => $afterSnapshot,
                        ]);
                        $this->logHorarioDebug('updateGrupoHorario:after_update', [
                            'horario_id' => $horario->horarios_id ?? null,
                            'after' => $this->horarioDebugSnapshot($horario),
                        ]);

                        Log::info('[HorariosProtegidos] Horario destino actualizado por drag and drop', [
                            'horarios_id' => $horario->horarios_id,
                            'grupo_id' => $horario->grupo_id,
                            'horarios_dia' => $horario->horarios_dia,
                            'horas_id' => $horario->horas_id,
                            'origen' => $horario->origen,
                            'protegido' => $horario->protegido,
                        ]);

                        return true;
                    } else {
                        $payloadCreateHorario = [
                            'horarios_dia' => $horarios_dia,
                            'horas_id' => $horas_id,
                            'grupo_id' => $grupo_id,
                            'espacios_id' => $id_espacios,
                            'profesores_id' => $profesores_id,
                            'origen' => 'drag_drop',
                            'protegido' => false,
                            'protegido_at' => null,
                        ];
                        $this->logHorarioDebug('updateGrupoHorario:before_create', [
                            'payload_create' => $payloadCreateHorario,
                            'context' => [
                                'horarios_id_param' => $horarios_id ?? null,
                                'anterior_id' => $anterior_id ?? null,
                                'grupo_id_param' => $grupo_id ?? null,
                                'horario_anterior_origen' => $horarioAnterior->origen ?? null,
                                'horario_anterior_protegido' => $horarioAnterior->protegido ?? null,
                            ],
                        ]);
                        $horario = Horario::create($payloadCreateHorario);
                        $this->pushUndoAction([
                            'type' => 'create',
                            'horario_id' => $horario->horarios_id,
                            'after' => $this->snapshotHorario($horario),
                        ]);
                        $this->logHorarioDebug('updateGrupoHorario:after_create', [
                            'horario_id' => $horario->horarios_id ?? null,
                            'created' => $this->horarioDebugSnapshot($horario),
                        ]);

                        Log::info('[HorariosProtegidos] Horario creado por drag and drop', [
                            'horarios_id' => $horario->horarios_id,
                            'grupo_id' => $horario->grupo_id,
                            'horarios_dia' => $horario->horarios_dia,
                            'horas_id' => $horario->horas_id,
                            'origen' => $horario->origen,
                            'protegido' => $horario->protegido,
                        ]);

                        return true;
                    }
                } else {
                    $queryCantidad = Horario::where('horarios_dia', $horarios_dia)
                                      ->where('horas_id', $horas_id)
                                      ->where('grupo_id', $grupo_id)
                                      ->where('profesores_id', $profesores_id);

                    if (is_null($id_espacios)) {
                        $queryCantidad->whereNull('espacios_id');
                    } else {
                        $queryCantidad->where('espacios_id', $id_espacios);
                    }

                    $cantidad = $queryCantidad->count();

                    if ($cantidad == 0) {
                        $payloadCreateHorario = [
                            'horarios_dia' => $horarios_dia,
                            'horas_id' => $horas_id,
                            'grupo_id' => $grupo_id,
                            'espacios_id' => $id_espacios,
                            'profesores_id' => $profesores_id,
                            'origen' => 'drag_drop',
                            'protegido' => false,
                            'protegido_at' => null,
                        ];
                        $this->logHorarioDebug('updateGrupoHorario:before_create', [
                            'payload_create' => $payloadCreateHorario,
                            'context' => [
                                'horarios_id_param' => $horarios_id ?? null,
                                'anterior_id' => $anterior_id ?? null,
                                'grupo_id_param' => $grupo_id ?? null,
                                'horario_anterior_origen' => $horarioAnterior->origen ?? null,
                                'horario_anterior_protegido' => $horarioAnterior->protegido ?? null,
                            ],
                        ]);
                        $horario = Horario::create($payloadCreateHorario);
                        $this->pushUndoAction([
                            'type' => 'create',
                            'horario_id' => $horario->horarios_id,
                            'after' => $this->snapshotHorario($horario),
                        ]);
                        $this->logHorarioDebug('updateGrupoHorario:after_create', [
                            'horario_id' => $horario->horarios_id ?? null,
                            'created' => $this->horarioDebugSnapshot($horario),
                        ]);

                        Log::info('[HorariosProtegidos] Horario creado por drag and drop', [
                            'horarios_id' => $horario->horarios_id,
                            'grupo_id' => $horario->grupo_id,
                            'horarios_dia' => $horario->horarios_dia,
                            'horas_id' => $horario->horas_id,
                            'origen' => $horario->origen,
                            'protegido' => $horario->protegido,
                        ]);

                        return true;
                    }
                }

                return false;
            });

            $this->emitTo('show-horarios','render');

            if (! $operacionRealizada) {
                return;
            }

            $this->emit('alert', 'El horario fue agregado satisfactoriamente');
        }
    }

    private function registrarGrupoInactivoPorCambioHorario(Horario $horarioOriginal, string $nuevoDia, int $nuevaHoraId): void
    {
        $fechaOriginal = Carbon::parse($horarioOriginal->horarios_dia)->toDateString();
        $nuevaFecha = Carbon::parse($nuevoDia)->toDateString();
        $modalidadId = $this->modalidad
            ?? $horarioOriginal->grupo?->modalidad_id
            ?? Grupo::where('grupo_id', $horarioOriginal->grupo_id)->value('modalidad_id');

        $esMismaFecha = $fechaOriginal === $nuevaFecha;
        $esMismaHora = (int) $horarioOriginal->horas_id === $nuevaHoraId;

        if ($esMismaFecha && $esMismaHora) {
            return;
        }

        GrupoInactivo::firstOrCreate([
            'grupo_id' => $horarioOriginal->grupo_id,
            'fecha' => $fechaOriginal,
            'horas_id' => $horarioOriginal->horas_id,
            'modalidad_id' => $modalidadId,
        ]);
    }

    public function obtenerProfesores($fecha,$hora,$modalidad_id)
    {
        $carbonFecha = Carbon::parse($fecha);
        $dayOfWeek = $carbonFecha->dayOfWeekIso; // 1 (Mon) to 7 (Sun)

        // Profesores con bloqueo en la fecha y hora especificadas
        $profesoresConBloqueo = BloqueosProfesores::where(function ($query) use ($carbonFecha, $dayOfWeek, $hora) {
            // Bloqueos de día completo
            $query->where(function ($q_full_day) use ($carbonFecha) {
                $q_full_day->whereNotNull('fecha')
                           ->where('fecha', $carbonFecha->toDateString());
            })
            // Bloqueos recurrentes
            ->orWhere(function ($q_recurrent) use ($dayOfWeek, $hora) {
                $q_recurrent->whereNull('fecha')
                            ->where('dias_id', $dayOfWeek)
                            ->where('horas_id', $hora); // $hora es horas_id
            });
        })->distinct()->pluck('profesor_id')->toArray();

        // Profesores ya ocupados con otro horario
        $profesoresOcupados = Horario::whereDate('horarios_dia', $carbonFecha->toDateString())
            ->where('horas_id', $hora)
            ->distinct()->pluck('profesores_id')->toArray();

        $todosExcluidos = array_unique(array_merge($profesoresConBloqueo, $profesoresOcupados));

        return Profesor::where('modalidad_id', $modalidad_id)
            ->where('profesores_activo', 1)
            ->whereNotIn('profesores_id', $todosExcluidos)
            ->pluck('profesores_id');
    }

    public function initializeDragAndDrop()
    {
        $this->dispatchBrowserEvent('initialize-drag-and-drop');
    }

    public function updatedidnivel($idnivel){

        $this->arr_capitulos = Capitulo::where('nivel_id',$idnivel)->get();
        $this->id_tematica = null;
        $this->arr_tematicas = collect([]);
        if ($this->arr_capitulos->isEmpty()) {
            $this->addError('id_capitulo', "No hay capitulos disponibles para este nivel") ;
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
