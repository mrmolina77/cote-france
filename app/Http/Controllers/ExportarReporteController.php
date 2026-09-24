<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\User;
use App\Services\Facturacion\ReporteFinancieroService;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarReporteController extends Controller
{
    public function __invoke(Request $request, ReporteFinancieroService $service, string $tipo, string $formato)
    {
        Gate::authorize('export-financial-reports');
        abort_unless(in_array($tipo,['grupo','curso','periodo','metodo','usuario','vencidos','eventos','diario','cierre'],true),404);
        abort_unless(in_array($formato,['csv','xlsx'],true),404);
        $rules=['curso_id'=>'nullable|integer|min:1','grupo_id'=>'nullable|integer|min:1','metodo_pago_id'=>'nullable|integer|min:1',
            'usuario_id'=>'nullable|integer|min:1|exists:users,id','cajero_id'=>'nullable|integer|min:1|exists:users,id','moneda'=>'nullable|regex:/^[A-Z]{3}$/','tipo_evento'=>'nullable|in:todos,cancelado,reembolsado','tipo'=>'nullable|in:todos,cancelado,reembolsado'];
        if(in_array($tipo,['diario','cierre'],true)) $rules['fecha']='required|date_format:Y-m-d';
        else { $rules['desde']='required|date_format:Y-m-d'; $rules['hasta']='required|date_format:Y-m-d|after_or_equal:desde'; $rules['corte']='required|date_format:Y-m-d'; }
        $data=$request->validate($rules);

        if(in_array($tipo,['diario','cierre'],true)) {
            $cajero=(int)($data['usuario_id']??auth()->id());
            abort_unless(User::whereKey($cajero)->exists(),422);
            [$rows,$extra]=$tipo==='diario'?$this->diario($service,$data['fecha'],$cajero):$this->cierre($service,$data['fecha'],$cajero);
            $data=['fecha'=>$data['fecha'],'cajero_id'=>$cajero];
        } else {
            $data['tipo']=$data['tipo_evento']??$data['tipo']??'todos';
            $data['created_by']=$data['usuario_id']??null; $data['confirmed_by']=$data['cajero_id']??null;
            $items=in_array($tipo,['grupo','curso','periodo','metodo','usuario'],true)?$service->agrupacion($tipo,$data):($tipo==='vencidos'?$service->vencidos($data):$service->eventos($data));
            $rows=$this->rows($tipo,$items); $extra=[];
        }
        $generado=now(config('app.timezone'))->format('Y-m-d H:i:s').' '.config('app.timezone');
        $meta=array_merge([['Reporte',$tipo],['Generado',$generado],['Zona horaria',config('app.timezone')],['Filtros',json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]],$extra,[[]]);
        return $this->download(array_merge($meta,$rows),'reporte-'.$tipo.'-'.now()->format('Ymd-His').'.'.$formato,$formato);
    }

    private function diario(ReporteFinancieroService $service,string $fecha,int $cajero): array
    {
        $r=$service->diario($fecha,$cajero);
        $rows=[['RESUMEN POR MÉTODO Y MONEDA'],['Método','Moneda','Movimientos','Bruto','Ajustes','Neto']];
        foreach($r['totales'] as $x)$rows[]=[$x['metodo'],$x['moneda'],$x['cantidad'],$x['bruto'],$x['ajustes'],$x['neto']];
        $rows[]=[];$rows[]=['DETALLE DE MOVIMIENTOS'];$rows[]=['Tipo','ID','Folio','Fecha operación/evento','Método','Registró','Cajero/actor','Moneda','Bruto','Ajuste','Neto'];
        foreach($r['pagos'] as $p)$rows[]=['Ingreso',$p->pago_id,$p->folio,$p->fecha_pago?->format('Y-m-d H:i:s'),$p->metodoPago?->nombre?:'Sin método',$p->createdBy?->name?:'Sin usuario registrado',$p->confirmedBy?->name?:'Sin cajero registrado',$p->moneda,$p->monto,'0.00',$p->monto];
        foreach($r['eventos'] as $p)$rows[]=['Ajuste '.$p->estado,$p->pago_id,$p->folio,($p->estado==='cancelado'?$p->fecha_cancelacion:$p->fecha_reembolso)?->format('Y-m-d H:i:s'),$p->metodoPago?->nombre?:'Sin método','No aplica',$p->cancelledBy?->name?:'No registrado',$p->moneda,'0.00',$p->monto,'-'.$p->monto];
        return [$rows,[['Fecha de operación',$fecha],['Cajero ID',$cajero],['Ventana','['.$r['inicio']->format('Y-m-d H:i:s').', '.$r['fin']->format('Y-m-d H:i:s').')']]];
    }

    private function cierre(ReporteFinancieroService $service,string $fecha,int $cajero): array
    {
        $cierre=CierreCaja::with(['cajero','cerradoPor'])->where('cajero_id',$cajero)->whereDate('fecha_operacion',$fecha)->first();
        if(!$cierre){ [$rows,$extra]=$this->diario($service,$fecha,$cajero); array_unshift($extra,['Estado','PRE-CIERRE, AÚN NO CERRADO']); return [$rows,$extra]; }
        $extra=[['Estado','CIERRE DEFINITIVO'],['Fecha de operación',$cierre->fecha_operacion->format('Y-m-d')],['Cajero',$cierre->cajero?->name?:'No registrado'],['Cerrado por',$cierre->cerradoPor?->name?:'No registrado'],['Cerrado en',$cierre->cerrado_en?->format('Y-m-d H:i:s')],['Ventana','['.$cierre->ventana_inicio->format('Y-m-d H:i:s').', '.$cierre->ventana_fin->format('Y-m-d H:i:s').')'],['Zona horaria del cierre',$cierre->zona_horaria]];
        $rows=[['TOTALES GUARDADOS EN EL SNAPSHOT'],['Combinación','Esperado','Contado','Diferencia']];
        foreach($cierre->totales_esperados as $clave=>$esperado)$rows[]=[$clave,$esperado,$cierre->importes_contados[$clave]??'No capturado',$cierre->diferencias[$clave]??'No disponible'];
        $rows[]=[];$rows[]=['MOVIMIENTOS GUARDADOS EN EL SNAPSHOT'];
        foreach(['pagos','eventos'] as $seccion){$rows[]=[strtoupper($seccion)]; foreach(($cierre->snapshot_movimientos[$seccion]??[]) as $mov)$rows[]=[json_encode($mov,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];}
        return [$rows,$extra];
    }

    private function download(array $rows,string $name,string $formato)
    {
        if($formato==='xlsx'){ $path=SimpleXlsx::create($rows); return response()->download($path,$name,['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true); }
        return new StreamedResponse(function()use($rows){$out=fopen('php://output','wb'); fwrite($out,"\xEF\xBB\xBF"); foreach($rows as $row)fputcsv($out,array_map([SimpleXlsx::class,'safe'],$row)); fclose($out);},200,['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="'.$name.'"']);
    }

    private function rows(string $tipo,$items): array
    {
        if(in_array($tipo,['grupo','curso','periodo','metodo','usuario'],true)) return array_merge([['Dimensión','Tipo de usuario','Cantidad de pagos','Moneda','Monto']],$items->map(fn($r)=>[$r['dimension'],$r['tipo_usuario']??'', $r['cantidad'],$r['moneda'],$r['monto']])->all());
        if($tipo==='vencidos') return array_merge([['Cargo','Alumno','Curso','Grupo','Concepto','Período','Vencimiento','Días vencidos','Moneda','Total','Pagado','Saldo']],$items->map(fn($c)=>[$c->cargo_id,trim(($c->inscripcion?->prospecto?->prospectos_nombres??'').' '.($c->inscripcion?->prospecto?->prospectos_apellidos??'')),$c->inscripcion?->cursos?->cursos_descripcion?:'Sin curso',$c->inscripcion?->grupo?->grupo_nombre?:'Sin grupo',$c->conceptoCobro?->nombre?:'Sin concepto',$c->periodo_anio&&$c->periodo_mes?sprintf('%04d-%02d',$c->periodo_anio,$c->periodo_mes):'Sin período',$c->fecha_vencimiento?->format('Y-m-d'),$c->dias_vencidos,$c->moneda,$c->total,$c->pagado,$c->saldo_pendiente])->all());
        return array_merge([['Tipo','Folio','Alumno','Método','Fecha evento','Actor del evento','Moneda','Monto total del pago','Motivo']],$items->map(fn($p)=>[$p->estado,$p->folio,trim(($p->inscripcion?->prospecto?->prospectos_nombres??'').' '.($p->inscripcion?->prospecto?->prospectos_apellidos??'')),$p->metodoPago?->nombre?:'Sin método',($p->estado==='cancelado'?$p->fecha_cancelacion:$p->fecha_reembolso)?->format('Y-m-d H:i'),$p->cancelledBy?->name?:'No registrado',$p->moneda,$p->monto,$p->motivo_cancelacion?:'Sin motivo registrado'])->all());
    }
}
