<?php

namespace App\Services\Facturacion;

use App\Models\CierreCaja;
use App\Models\User;
use App\Support\FinancialPermissions;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CerrarCajaService
{
    private const IMPORTE_PATTERN = '/^(?:0|[1-9]\d{0,11})(?:\.\d{1,2})?$/D';

    public function __construct(private ReporteFinancieroService $reportes) {}

    public function cerrar(User $actor, int $cajeroId, string $fecha, array $contados, ?string $observaciones): CierreCaja
    {
        $this->autorizar($actor, $cajeroId);
        $this->reportes->ventana($fecha);
        if ($observaciones !== null && mb_strlen($observaciones) > 2000) {
            throw ValidationException::withMessages(['observaciones' => 'Las observaciones no pueden exceder 2000 caracteres.']);
        }

        try {
            return DB::transaction(function () use ($actor, $cajeroId, $fecha, $contados, $observaciones) {
                if (CierreCaja::query()->where('cajero_id', $cajeroId)->whereDate('fecha_operacion', $fecha)->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['fechaCaja' => 'Ya existe un cierre inmutable para este cajero y fecha.']);
                }
                $resumen = $this->reportes->diario($fecha, $cajeroId);
                $permitidas = $this->reportes->combinacionesContables($resumen);
                $normalizados = $this->validarContados($contados, $permitidas->keys()->all());
                $esperados = []; $diferencias = [];
                foreach ($permitidas as $clave => $fila) {
                    $esperado = (string) $fila['neto'];
                    if ((int) $fila['cantidad'] > 0 && ! array_key_exists($clave, $normalizados)) {
                        throw ValidationException::withMessages(["contados.$clave" => 'Captura explícitamente el importe contado.']);
                    }
                    if (! array_key_exists($clave, $normalizados)) continue;
                    $esperados[$clave] = $esperado;
                    $diferencias[$clave] = (string) BigDecimal::of($normalizados[$clave])->minus($esperado)->toScale(2, RoundingMode::UNNECESSARY);
                }
                return CierreCaja::query()->create([
                    'cajero_id'=>$cajeroId, 'fecha_operacion'=>$fecha,
                    'ventana_inicio'=>$resumen['inicio'], 'ventana_fin'=>$resumen['fin'], 'zona_horaria'=>config('app.timezone'),
                    'totales_esperados'=>$esperados, 'importes_contados'=>$normalizados, 'diferencias'=>$diferencias,
                    'snapshot_movimientos'=>[
                        'pagos'=>$resumen['pagos']->map(fn ($p) => ['pago_id'=>$p->pago_id,'folio'=>$p->folio,'fecha'=>$p->fecha_pago?->format('Y-m-d H:i:s'),'metodo_id'=>$p->metodo_pago_id,'metodo'=>$p->metodoPago?->nombre ?: 'Sin método','monto'=>(string)$p->monto,'moneda'=>$p->moneda,'registrado_por'=>$p->createdBy?->name ?: 'Sin usuario registrado','cajero'=>$p->confirmedBy?->name ?: 'Sin cajero registrado'])->all(),
                        'eventos'=>$resumen['eventos']->map(fn ($p) => ['pago_id'=>$p->pago_id,'folio'=>$p->folio,'tipo'=>$p->estado,'fecha'=>($p->estado==='cancelado'?$p->fecha_cancelacion:$p->fecha_reembolso)?->format('Y-m-d H:i:s'),'metodo_id'=>$p->metodo_pago_id,'metodo'=>$p->metodoPago?->nombre ?: 'Sin método','monto'=>(string)$p->monto,'moneda'=>$p->moneda,'actor'=>$p->cancelledBy?->name ?: 'No registrado'])->all(),
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

    public function validarContados(array $contados, array $clavesPermitidas): array
    {
        $permitidas = array_fill_keys($clavesPermitidas, true);
        $normalizados = [];
        foreach ($contados as $clave => $importe) {
            if (! is_string($clave) || ! isset($permitidas[$clave])) {
                throw ValidationException::withMessages(['contados' => 'Se recibió una combinación método/moneda desconocida.']);
            }
            if (! is_string($importe) && ! is_int($importe)) {
                throw ValidationException::withMessages(["contados.$clave" => 'El importe contado debe ser un decimal no negativo.']);
            }
            $texto = (string) $importe;
            if (! preg_match(self::IMPORTE_PATTERN, $texto)) {
                throw ValidationException::withMessages(["contados.$clave" => 'Usa un decimal no negativo, sin exponentes y con máximo dos decimales.']);
            }
            $normalizados[$clave] = (string) BigDecimal::of($texto)->toScale(2, RoundingMode::UNNECESSARY);
        }
        return $normalizados;
    }

    private function autorizar(User $actor, int $cajeroId): void
    {
        if (! FinancialPermissions::allows($actor, FinancialPermissions::CLOSE_CASH)) throw new AuthorizationException();
        $rol = optional($actor->role)->roles_codigo;
        if ($rol === 'caja' && $actor->getKey() !== $cajeroId) throw new AuthorizationException('Un usuario de caja solo puede cerrar su propia caja.');
    }
}
