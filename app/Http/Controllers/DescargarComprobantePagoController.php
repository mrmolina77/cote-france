<?php

namespace App\Http\Controllers;

use App\Models\ComprobantePago;
use App\Models\Pago;
use App\Services\Facturacion\GeneradorComprobantePagoService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class DescargarComprobantePagoController extends Controller
{
    public function __invoke(Pago $pago, ComprobantePago $comprobante, GeneradorComprobantePagoService $servicio)
    {
        Gate::authorize('view-payment-documents');
        abort_unless((int) $comprobante->pago_id === (int) $pago->getKey(), 404);
        abort_unless($comprobante->disco === ComprobantePago::DISCO_PRIVADO && $comprobante->mime_type === ComprobantePago::MIME_PDF, 404);
        abort_unless($servicio->rutaPermitida($comprobante->ruta_pdf), 404);
        $disco = Storage::disk(ComprobantePago::DISCO_PRIVADO);
        abort_unless($disco->exists($comprobante->ruta_pdf), 404);
        $bytes = $disco->get($comprobante->ruta_pdf);
        abort_unless(hash_equals($comprobante->hash_sha256, hash('sha256', $bytes)), 404);

        return response($bytes, 200, [
            'Content-Type'=>'application/pdf',
            'Content-Disposition'=>'attachment; filename="'.$comprobante->folio.'.pdf"',
            'X-Content-Type-Options'=>'nosniff',
            'Cache-Control'=>'private, no-store, max-age=0',
            'Pragma'=>'no-cache',
        ]);
    }
}
