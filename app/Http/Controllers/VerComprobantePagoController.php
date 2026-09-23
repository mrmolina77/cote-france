<?php

namespace App\Http\Controllers;

use App\Models\ComprobantePago;
use App\Models\Pago;
use Illuminate\Support\Facades\Gate;

class VerComprobantePagoController extends Controller
{
    public function __invoke(Pago $pago, ComprobantePago $comprobante)
    {
        Gate::authorize('view-payment-documents');
        abort_unless((int) $comprobante->pago_id === (int) $pago->getKey(), 404);
        return view('comprobantes-pago.show', compact('pago', 'comprobante'));
    }
}
