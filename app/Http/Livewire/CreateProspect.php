<?php

namespace App\Http\Livewire;

use App\Models\Curso;
use Livewire\Component;
use App\Models\Origen;
use App\Models\Seguimiento;
use App\Models\Estatu;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\Profesor;
use App\Models\Prospecto;
use App\Models\User;
use App\Notifications\ClassCreated;
use Illuminate\Support\Facades\DB;

class CreateProspect extends Component
{

    public $open = false;

    public $prospectos_nombres,$prospectos_apellidos,$prospectos_telefono1,$prospectos_telefono2;
    public $prospectos_correo,$origenes_id,$seguimientos_id,$estatus_id,$grupoid,$horarios_id;
    public $prospectos_comentarios,$prospectos_fecha,$prospectos_clase_fecha;
    public $prospectos_clase_hora,$horarios,$modalidad_id,$cursos_id;

    protected $rules = [
        'prospectos_nombres'=>'required|min:3|max:50',
        'prospectos_apellidos'=>'nullable|min:3|max:50',
        'prospectos_telefono1'=>'required|numeric',
        'prospectos_telefono2'=>'nullable|numeric',
        'prospectos_correo'=>'nullable|email|max:100',
        'origenes_id'=>'nullable',
        'seguimientos_id'=>'nullable',
        'estatus_id'=>'nullable',
        'cursos_id'=>'nullable',
        'modalidad_id'=>'nullable',
        'prospectos_comentarios'=>'nullable|min:7|max:255',
        'prospectos_fecha'=>'required|date',
    ];



    /* public function updated($propertyName){
        $this->validateOnly($propertyName);
    } */

    public function boot()
    {
        $this->prospectos_fecha = date('Y-m-d');
        $this->horarios = collect([]);
    }

    public function updatedProspectosClaseFecha($value)
    {
        $this->prospectos_clase_hora = null;
        $this->grupoid = null;
    }

    public function updatedProspectosClaseHora($value)
    {
        $this->grupoid = null;
    }

    public function save(){
        $this->validate();

        if ($this->seguimientos_id == 2) {
            if (empty($this->prospectos_clase_fecha)) {
                $this->addError('prospectos_clase_fecha', "La fecha es obligatoria");
            }
            if (empty($this->prospectos_clase_hora)) {
                $this->addError('prospectos_clase_hora', "La hora es obligatoria");
            }
            if (empty($this->grupoid)) {
                $this->addError('grupoid', "El grupo es obligatorio");
            }
            if (empty($this->modalidad_id)) {
                $this->addError('modalidad_id', "La modalidad es obligatoria");
            }
            if ($this->getErrorBag()->any()) {
                return;
            }
        }

        if ($this->seguimientos_id == 8 and ($this->grupoid == null or $this->cursos_id == null)){
            if ($this->cursos_id == null)
                $this->addError('cursos_id', "El curso es obligatorio");
            if ($this->grupoid == null)
                $this->addError('grupoid', "El grupo es obligatorio");
        } else {
             DB::beginTransaction();
            try {
                $prospecto = new Prospecto();
                $prospecto->prospectos_nombres = $this->prospectos_nombres;
                $prospecto->prospectos_apellidos = $this->prospectos_apellidos;
                $prospecto->prospectos_telefono1 = $this->prospectos_telefono1;
                $prospecto->prospectos_telefono2 = $this->prospectos_telefono2;
                $prospecto->prospectos_correo = $this->prospectos_correo;
                $prospecto->origenes_id = $this->origenes_id;
                $prospecto->cursos_id = $this->cursos_id;
                $prospecto->seguimientos_id = $this->seguimientos_id;
                if ($this->seguimientos_id == 8 and $this->grupoid != null){
                    $prospecto->estatus_id = 2;
                } else {
                    $prospecto->estatus_id = $this->estatus_id;
                }
                $prospecto->modalidad_id = $this->modalidad_id;
                $prospecto->grupo_id = $this->grupoid;
                $prospecto->horarios_id = $this->horarios_id;
                $prospecto->prospectos_comentarios = $this->prospectos_comentarios;
                $prospecto->prospectos_fecha = $this->prospectos_fecha;
                $prospecto->save();
                if ($this->seguimientos_id == 2){
                    \App\Models\ClasePrueba::create([
                        'prospectos_id' => $prospecto->prospectos_id,
                        'grupo_id' => $this->grupoid,
                        'horarios_dia' => $this->prospectos_clase_fecha,
                        'horas_id' => $this->prospectos_clase_hora,
                        'modalidad_id' => $this->modalidad_id,
                    ]);

                    $horario = Horario::where('grupo_id', $this->grupoid)
                        ->whereDate('horarios_dia', $this->prospectos_clase_fecha)
                        ->where('horas_id', $this->prospectos_clase_hora)
                        ->first();

                    if ($horario && $horario->profesores_id) {
                        $profesor = Profesor::find($horario->profesores_id);
                        if ($profesor) {
                            $datos = ['nombre'=>$this->prospectos_nombres.' '.$this->prospectos_apellidos,
                                      'hora'=>$horario->hora->horas_desde,
                                      'prospectos_telefono'=>$this->prospectos_telefono1,
                                      'fecha'=>$horario->horarios_dia,
                                      'prospectos_correo'=>$this->prospectos_correo];
                            $profesor->notify(new ClassCreated($datos));
                        }
                    }
                }

                if ($this->seguimientos_id == 8 and $this->grupoid != null){

                    Inscripcion::create([
                        'prospectos_id' =>$prospecto->prospectos_id,
                        'cursos_id' =>$this->cursos_id,
                        'grupo_id' =>$this->grupoid,
                        'fecha_inscripcion' =>$this->prospectos_fecha,
                    ]);

                }

                DB::commit();

                $this->reset(['open','prospectos_nombres','prospectos_apellidos','prospectos_telefono1','prospectos_telefono2',
                'prospectos_correo','origenes_id','seguimientos_id','estatus_id','grupoid','horarios_id',
                'prospectos_comentarios','prospectos_clase_fecha','prospectos_clase_hora','modalidad_id']);
                $this->emitTo('show-prospectos','render');
                $this->emit('alert','El prospecto fue agregado satifactoriamente');
            } catch (\Throwable $th) {
                DB::rollBack(); // Revertir los cambios si algo falla
                $this->emit('alert','El prospecto presento problema no fue agregado satifactoriamente '.$th->getMessage(),'Error!','error');
            }
        }
    }

    public function render()
    {
        $origenes = Origen::all();
        $seguimientos = Seguimiento::all();
        $estatus = Estatu::all();
        $grupos = Grupo::all();
        $modalidades = Modalidad::all();
        $cursos = Curso::all();

        $lista_horas = collect([]);
        if ($this->prospectos_clase_fecha) {
            $dayOfWeek = \Carbon\Carbon::parse($this->prospectos_clase_fecha)->dayOfWeekIso;
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $lista_horas = \App\Models\Hora::where('tipo', 1)->get();
            } elseif ($dayOfWeek == 6) {
                $lista_horas = \App\Models\Hora::where('tipo', 2)->get();
            }
        }

        $lista_grupos = collect([]);
        if ($this->prospectos_clase_hora && $this->prospectos_clase_fecha) {
            $fecha = \Carbon\Carbon::parse($this->prospectos_clase_fecha);
            $inicioSemana = $fecha->copy()->startOfWeek()->toDateString();
            $activeWeek = \App\Models\ActiveWeek::where('start_date', $inicioSemana)->first();
            $is_active = $activeWeek ? $activeWeek->is_active : false;

            if ($is_active) {
                $gruposIds = \App\Models\Horario::whereDate('horarios_dia', $this->prospectos_clase_fecha)
                    ->where('horas_id', $this->prospectos_clase_hora)
                    ->pluck('grupo_id');
            } else {
                $fechaStr = $fecha->toDateString();
                $dayOfWeek = $fecha->dayOfWeekIso;

                $horarioIds = \App\Models\Horario::whereDate('horarios_dia', $fechaStr)
                    ->where('horas_id', $this->prospectos_clase_hora)
                    ->pluck('grupo_id');

                $inactivos = \App\Models\GrupoInactivo::where('fecha', $fechaStr)
                    ->where('horas_id', $this->prospectos_clase_hora)
                    ->pluck('grupo_id');

                $detallesIds = \App\Models\GruposDetalles::join('grupos', 'grupos.grupo_id', '=', 'grupos_detalles.grupo_id')
                    ->where('grupos.estado_id', 1)
                    ->where('grupos_detalles.dias_id', $dayOfWeek)
                    ->where('grupos_detalles.horas_id', $this->prospectos_clase_hora)
                    ->where(function($q) use ($fechaStr) {
                        $q->whereNull('grupos.fecha_inicio')
                          ->orWhereDate('grupos.fecha_inicio', '<=', $fechaStr);
                    })
                    ->whereNotIn('grupos_detalles.grupo_id', $inactivos)
                    ->pluck('grupos_detalles.grupo_id');

                $gruposIds = $horarioIds->merge($detallesIds)->unique();
            }
            
            $lista_grupos = \App\Models\Grupo::whereIn('grupo_id', $gruposIds)->orderBy('grupo_nombre')->get();
        }

        return view('livewire.create-prospect',['origenes'=>$origenes
                                               ,'seguimientos'=>$seguimientos
                                               ,'grupos'=>$grupos
                                               ,'modalidades'=>$modalidades
                                               ,'cursos'=>$cursos
                                               ,'estatus'=>$estatus
                                               ,'lista_horas'=>$lista_horas
                                               ,'lista_grupos'=>$lista_grupos]);
    }

    public function updatedGrupoid($grupo_id){
        $this->horarios =  Horario::where('grupo_id',$grupo_id)->where('horarios_dia','>=',date('Y-m-d'))->get();
    }
}
