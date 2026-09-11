<?php

namespace App\Http\Controllers;

use App\Models\ArchivoPago;
use App\Models\Pago;
use App\Services\Facturacion\ArchivoPagoService;
use Illuminate\Support\Facades\Storage;

class DescargarArchivoPagoController extends Controller
{
    public function __invoke(Pago $pago, ArchivoPago $archivo, ArchivoPagoService $servicio)
    {
        abort_unless((int) $archivo->pago_id === (int) $pago->getKey(), 404);
        abort_unless($archivo->disco === ArchivoPagoService::DISCO && $servicio->rutaPermitida($archivo->ruta), 404);
        abort_unless(in_array($archivo->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true), 404);

        $disco = Storage::disk(ArchivoPagoService::DISCO);
        abort_unless($disco->exists($archivo->ruta), 404);

        return $disco->download($archivo->ruta, $archivo->nombre_original, [
            'Content-Type' => $archivo->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
