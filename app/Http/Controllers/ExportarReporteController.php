<?php

namespace App\Http\Controllers;

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
        abort_unless(in_array($tipo,['grupo','curso','periodo','metodo','usuario','vencidos','eventos'],true),404);
        abort_unless(in_array($formato,['csv','xlsx'],true),404);
        $data=$request->validate(['desde'=>'required|date_format:Y-m-d','hasta'=>'required|date_format:Y-m-d|after_or_equal:desde','corte'=>'required|date_format:Y-m-d','curso_id'=>'nullable|integer|min:1','grupo_id'=>'nullable|integer|min:1','metodo_pago_id'=>'nullable|integer|min:1','usuario_id'=>'nullable|integer|min:1','moneda'=>'nullable|regex:/^[A-Z]{3}$/','tipo'=>'nullable|in:todos,cancelado,reembolsado']);
        $items=in_array($tipo,['grupo','curso','periodo','metodo','usuario'],true)?$service->agrupacion($tipo,$data):($tipo==='vencidos'?$service->vencidos($data):$service->eventos($data+['tipo'=>'todos']));
        $rows=$this->rows($tipo,$items); $meta=[['Reporte',$tipo],['Generado',now(config('app.timezone'))->format('Y-m-d H:i:s').' '.config('app.timezone')],['Filtros',json_encode($data,JSON_UNESCAPED_UNICODE)],[]];
        $all=array_merge($meta,$rows); $name='reporte-'.$tipo.'-'.now()->format('Ymd-His').'.'.$formato;
        if($formato==='xlsx'){ $path=SimpleXlsx::create($all); return response()->download($path,$name,['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true); }
        return new StreamedResponse(function()use($all){$out=fopen('php://output','wb'); fwrite($out,"\xEF\xBB\xBF"); foreach($all as $row)fputcsv($out,array_map([SimpleXlsx::class,'safe'],$row)); fclose($out);},200,['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="'.$name.'"']);
    }

    private function rows(string $tipo,$items): array
    {
        if(in_array($tipo,['grupo','curso','periodo','metodo','usuario'],true)) return array_merge([['Dimensión','Cantidad de pagos','Moneda','Monto']],$items->map(fn($r)=>[$r['dimension'],$r['cantidad'],$r['moneda'],$r['monto']])->all());
        if($tipo==='vencidos') return array_merge([['Cargo','Alumno','Curso','Grupo','Concepto','Período','Vencimiento','Días vencidos','Moneda','Total','Pagado','Saldo']],$items->map(fn($c)=>[$c->cargo_id,trim(($c->inscripcion?->prospecto?->prospectos_nombres??'').' '.($c->inscripcion?->prospecto?->prospectos_apellidos??'')),$c->inscripcion?->cursos?->cursos_descripcion?:'Sin curso',$c->inscripcion?->grupo?->grupo_nombre?:'Sin grupo',$c->conceptoCobro?->nombre?:'Sin concepto',sprintf('%04d-%02d',$c->periodo_anio,$c->periodo_mes),$c->fecha_vencimiento?->format('Y-m-d'),$c->dias_vencidos,$c->moneda,$c->total,$c->pagado,$c->saldo_pendiente])->all());
        return array_merge([['Tipo','Folio','Alumno','Método','Fecha evento','Usuario','Moneda','Monto total del pago','Motivo']],$items->map(fn($p)=>[$p->estado,$p->folio,trim(($p->inscripcion?->prospecto?->prospectos_nombres??'').' '.($p->inscripcion?->prospecto?->prospectos_apellidos??'')),$p->metodoPago?->nombre?:'Sin método',($p->estado==='cancelado'?$p->fecha_cancelacion:$p->fecha_reembolso)?->format('Y-m-d H:i'),$p->cancelledBy?->name?:'Sin usuario',$p->moneda,$p->monto,$p->motivo_cancelacion?:'Sin motivo registrado'])->all());
    }
}
