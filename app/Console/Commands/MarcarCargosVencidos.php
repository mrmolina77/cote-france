<?php

namespace App\Console\Commands;

use App\Services\Facturacion\ActualizadorCargosVencidosService;
use Illuminate\Console\Command;

class MarcarCargosVencidos extends Command
{
    protected $signature = 'cargos:marcar-vencidos';

    protected $description = 'Marca como vencidos los cargos abiertos cuya fecha de vencimiento ya pasó';

    public function handle(ActualizadorCargosVencidosService $service): int
    {
        $this->info('Cargos marcados como vencidos: '.$service->actualizar());

        return self::SUCCESS;
    }
}
