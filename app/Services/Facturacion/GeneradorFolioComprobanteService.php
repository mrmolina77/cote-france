<?php

namespace App\Services\Facturacion;

use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class GeneradorFolioComprobanteService
{
    /** @return array{folio:string,anio:int,secuencia:int} */
    public function generar(?CarbonInterface $fecha = null): array
    {
        $fecha = ($fecha ?: now())->copy()->setTimezone(config('app.timezone'));
        $anio = (int) $fecha->format('Y');

        return DB::transaction(function () use ($anio) {
            for ($intento = 0; $intento < 3; $intento++) {
                try {
                    DB::table('consecutivos_comprobante_pago')->insertOrIgnore([
                        'anio' => $anio, 'ultimo_consecutivo' => 0,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $fila = DB::table('consecutivos_comprobante_pago')->where('anio', $anio)->lockForUpdate()->first();
                    $secuencia = ((int) $fila->ultimo_consecutivo) + 1;
                    DB::table('consecutivos_comprobante_pago')->where('anio', $anio)->update([
                        'ultimo_consecutivo' => $secuencia, 'updated_at' => now(),
                    ]);
                    return ['folio' => sprintf('REC-%04d-%06d', $anio, $secuencia), 'anio' => $anio, 'secuencia' => $secuencia];
                } catch (QueryException $e) {
                    if ($intento === 2 || ! in_array((string) $e->getCode(), ['23000', '23505'], true)) throw $e;
                }
            }
        }, 3);
    }
}
