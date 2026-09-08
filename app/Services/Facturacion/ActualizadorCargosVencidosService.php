<?php

namespace App\Services\Facturacion;

use App\Models\Cargo;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ActualizadorCargosVencidosService
{
    public function actualizar(?CarbonInterface $fechaCorte = null): int
    {
        $zonaHoraria = config('app.timezone');
        $corte = $fechaCorte
            ? CarbonImmutable::instance($fechaCorte)->setTimezone($zonaHoraria)
            : CarbonImmutable::now($zonaHoraria);

        return DB::transaction(function () use ($corte): int {
            return Cargo::query()
                ->elegiblesParaVencimiento($corte->toDateString())
                ->update(['estado' => Cargo::ESTADO_VENCIDO]);
        });
    }
}
