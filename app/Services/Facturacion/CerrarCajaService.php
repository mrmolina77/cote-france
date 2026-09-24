<?php

namespace App\Services\Facturacion;

use App\Models\CierreCaja;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CerrarCajaService
{
    public function __construct(private ReporteFinancieroService $reportes) {}

    public function cerrar(User $actor, int $cajeroId, string $fecha, array $contados, ?string $observaciones): CierreCaja
    {
        try {
            return DB::transaction(function () use ($actor, $cajeroId, $fecha, $contados, $observaciones) {
                if (CierreCaja::query()->where('cajero_id', $cajeroId)->whereDate('fecha_operacion', $fecha)->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['fechaCaja' => 'Ya existe un cierre inmutable para este cajero y fecha.']);
                }
                $resumen = $this->reportes->diario($fecha, $cajeroId);
                $esperados = []; $diferencias = [];
                foreach ($resumen['totales'] as $fila) {
                    $clave = $fila['metodo'].'|'.$fila['moneda'];
                    $esperados[$clave] = $fila['neto'];
                    $diferencias[$clave] = (string) BigDecimal::of((string) ($contados[$clave] ?? '0.00'))->minus($fila['neto'])->toScale(2, RoundingMode::UNNECESSARY);
                }
                return CierreCaja::query()->create([
                    'cajero_id'=>$cajeroId, 'fecha_operacion'=>$fecha,
                    'ventana_inicio'=>$resumen['inicio'], 'ventana_fin'=>$resumen['fin'], 'zona_horaria'=>config('app.timezone'),
                    'totales_esperados'=>$esperados, 'importes_contados'=>$contados, 'diferencias'=>$diferencias,
                    'snapshot_movimientos'=>[
                        'pagos'=>$resumen['pagos']->map(fn ($p) => ['pago_id'=>$p->pago_id,'folio'=>$p->folio,'monto'=>(string)$p->monto,'moneda'=>$p->moneda])->all(),
                        'eventos'=>$resumen['eventos']->map(fn ($p) => ['pago_id'=>$p->pago_id,'tipo'=>$p->estado,'monto'=>(string)$p->monto,'moneda'=>$p->moneda])->all(),
                        'totales'=>$resumen['totales']->all(),
                    ],
                    'cerrado_por'=>$actor->id, 'cerrado_en'=>now(), 'observaciones'=>$observaciones, 'estado'=>'cerrado',
                ]);
            }, 3);
        } catch (QueryException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages(['fechaCaja' => 'Ya existe un cierre inmutable para este cajero y fecha.']);
            }
            throw $e;
        }
    }
}
