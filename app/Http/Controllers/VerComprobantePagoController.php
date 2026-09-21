<?php

namespace App\Http\Controllers;

use App\Models\ComprobantePago;
use App\Models\Pago;

class VerComprobantePagoController extends Controller
{
    public function __invoke(Pago $pago, ComprobantePago $comprobante)
    {
        abort_unless((int) $comprobante->pago_id === (int) $pago->getKey(), 404);
        return view('comprobantes-pago.show', compact('pago', 'comprobante'));
    }
}
