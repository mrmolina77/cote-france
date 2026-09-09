<?php

namespace App\Services\Facturacion;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class GeneradorFolioPagoService
{
    /**
     * Reserva el siguiente folio. La futura inserción del pago debe ejecutarse en
     * la misma transacción de negocio para que ambos cambios hagan rollback juntos.
     */
    public function generar($fechaPago, string $zonaHoraria): string
    {
        return DB::transaction(function () use ($fechaPago, $zonaHoraria) {
            $anio = $this->obtenerAnio($fechaPago, $zonaHoraria);
            $ahora = now();

            DB::table('consecutivos_pago')->insertOrIgnore([
                'anio' => $anio,
                'ultimo_consecutivo' => 0,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            $contador = DB::table('consecutivos_pago')
                ->where('anio', $anio)
                ->lockForUpdate()
                ->first();

            $consecutivo = $contador->ultimo_consecutivo + 1;
            DB::table('consecutivos_pago')->where('anio', $anio)->update([
                'ultimo_consecutivo' => $consecutivo,
                'updated_at' => $ahora,
            ]);

            return sprintf('PAG-%d-%06d', $anio, $consecutivo);
        });
    }

    private function obtenerAnio($fechaPago, string $zonaHoraria): int
    {
        if ($fechaPago instanceof DateTimeInterface) {
            return Carbon::instance($fechaPago)->setTimezone($zonaHoraria)->year;
        }

        return Carbon::parse($fechaPago, $zonaHoraria)->year;
    }
}
