<?php

namespace App\Http\Livewire;

use App\Models\CierreCaja;
use App\Models\Curso;
use App\Models\Grupo;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\User;
use App\Services\Facturacion\CerrarCajaService;
use App\Services\Facturacion\ReporteFinancieroService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ShowReportes extends Component
{
    public $reporte='grupo', $desde='', $hasta='', $corte='', $fechaCaja='', $cursoId='', $grupoId='', $metodoId='', $moneda='', $usuarioId='', $cajeroId='', $tipoEvento='todos';
    public $observaciones='', $contados=[];
    protected $queryString = ['reporte','desde','hasta','corte','cursoId','grupoId','metodoId','moneda','usuarioId','cajeroId','tipoEvento'];

    public function mount(): void
    {
        Gate::authorize('view-financial-reports');
        $hoy=now(config('app.timezone')); $this->desde=$this->desde ?: $hoy->copy()->startOfMonth()->toDateString();
        $this->hasta=$this->hasta ?: $hoy->toDateString(); $this->corte=$this->corte ?: $hoy->toDateString(); $this->fechaCaja=$this->fechaCaja ?: $hoy->toDateString();
    }

    public function updated($campo): void { Gate::authorize('view-financial-reports'); if (in_array($campo,['desde','hasta','corte','fechaCaja'],true)) $this->validar(); }
    public function aplicar(): void { Gate::authorize('view-financial-reports'); $this->validar(); }
    public function limpiar(): void { Gate::authorize('view-financial-reports'); $this->reset(['cursoId','grupoId','metodoId','moneda','usuarioId','cajeroId']); $this->tipoEvento='todos'; $this->mount(); $this->resetErrorBag(); }

    public function cerrarCaja(CerrarCajaService $service): void
    {
        Gate::authorize('close-cash'); $this->validar();
        $cajero=$this->cajeroSeleccionado(true);
        $service->cerrar(auth()->user(),$cajero,$this->fechaCaja,$this->contados,trim($this->observaciones) ?: null);
        session()->flash('reportes-ok','Cierre guardado. El snapshot es inmutable.');
    }

    public function render(ReporteFinancieroService $service)
    {
        Gate::authorize('view-financial-reports'); $this->validar(false);
        $permitidos=['grupo','curso','periodo','metodo','usuario','vencidos','eventos','diario','cierre'];
        if (!in_array($this->reporte,$permitidos,true)) $this->reporte='grupo';
        $f=$this->filtros(); $filas=collect(); $diario=null; $combinaciones=collect();
        if (in_array($this->reporte,['grupo','curso','periodo','metodo','usuario'],true)) $filas=$service->agrupacion($this->reporte,$f);
        elseif ($this->reporte==='vencidos') $filas=$service->vencidos($f);
        elseif ($this->reporte==='eventos') $filas=$service->eventos($f);
        elseif (in_array($this->reporte,['diario','cierre'],true)) {
            $diario=$service->diario($this->fechaCaja,$this->cajeroSeleccionado());
            $combinaciones=$service->combinacionesContables($diario);
        }
        return view('livewire.show-reportes',[
            'filas'=>$filas,'diario'=>$diario,'combinaciones'=>$combinaciones,'cajeroSeleccionado'=>$this->cajeroSeleccionado(),
            'cierreExistente'=>$this->reporte==='cierre' ? CierreCaja::with(['cajero','cerradoPor'])->where('cajero_id',$this->cajeroSeleccionado())->whereDate('fecha_operacion',$this->fechaCaja)->first() : null,
            'cursos'=>Curso::orderBy('cursos_descripcion')->get(),'grupos'=>Grupo::orderBy('grupo_nombre')->get(),'metodos'=>MetodoPago::ordenados()->get(),
            'usuarios'=>User::whereHas('role',fn($q)=>$q->whereIn('roles_codigo',['admin','caja','contabilidad']))->orderBy('name')->get(),
            'zona'=>config('app.timezone'),
        ]);
    }

    public function filtros(): array
    {
        $cajero=optional(auth()->user()->role)->roles_codigo==='caja' ? (int)auth()->id() : $this->id($this->cajeroId);
        return ['desde'=>$this->desde,'hasta'=>$this->hasta,'corte'=>$this->corte,'curso_id'=>$this->id($this->cursoId),'grupo_id'=>$this->id($this->grupoId),'metodo_pago_id'=>$this->id($this->metodoId),'moneda'=>preg_match('/^[A-Z]{3}$/',$this->moneda)?$this->moneda:null,'created_by'=>$this->id($this->usuarioId),'confirmed_by'=>$cajero,'cancelled_by'=>optional(auth()->user()->role)->roles_codigo==='caja'?(int)auth()->id():$this->id($this->usuarioId),'usuario_id'=>$this->id($this->usuarioId),'tipo'=>$this->tipoEvento];
    }

    /** Return a display value without converting money to binary floating point. */
    public function diferenciaPreliminar(string $clave, string $esperado): ?string
    {
        if (! array_key_exists($clave, $this->contados)) return null;
        $contado = $this->contados[$clave];
        if ((! is_string($contado) && ! is_int($contado)) || ! preg_match('/^(?:0|[1-9]\d{0,11})(?:\.\d{1,2})?$/D', (string) $contado)) return null;

        return (string) BigDecimal::of((string) $contado)->minus($esperado)->toScale(2, RoundingMode::UNNECESSARY);
    }

    private function id($v): ?int { return ctype_digit((string)$v) && (int)$v>0 ? (int)$v : null; }
    private function cajeroSeleccionado(bool $paraCerrar=false): int
    {
        $actor=auth()->user(); $rol=optional($actor->role)->roles_codigo;
        if($rol==='caja') return (int)$actor->getKey();
        $id=$this->id($this->cajeroId ?: $this->usuarioId);
        if($id && User::whereKey($id)->exists()) return $id;
        return (int)$actor->getKey();
    }
    private function validar(bool $lanzar=true): bool
    {
        $errores=[]; foreach(['desde','hasta','corte','fechaCaja'] as $c) { $v=$this->$c; $d=\DateTimeImmutable::createFromFormat('!Y-m-d',(string)$v); if(!$d || $d->format('Y-m-d')!==$v)$errores[$c]='Indica una fecha real (AAAA-MM-DD).'; }
        if(!isset($errores['desde'],$errores['hasta']) && $this->desde>$this->hasta)$errores['hasta']='La fecha final no puede ser anterior a la inicial.';
        if($lanzar && $errores) throw \Illuminate\Validation\ValidationException::withMessages($errores);
        foreach($errores as $c=>$m)$this->addError($c,$m); return !$errores;
    }
}
