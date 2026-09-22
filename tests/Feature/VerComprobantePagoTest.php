<?php

namespace Tests\Feature;

use App\Services\Facturacion\GeneradorComprobantePagoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class VerComprobantePagoTest extends ComprobantePagoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-21 12:00:00');
        Storage::fake('local');
    }

    public function test_qr_firmado_exige_sesion_permiso_y_firma_intacta(): void
    {
        $admin = $this->user('admin');
        ['pago' => $pago] = $this->pagoConfirmado($admin);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $url = URL::signedRoute('facturacion.comprobantes.ver', ['pago' => $pago, 'comprobante' => $recibo]);

        $this->get($url)->assertRedirect('/login');
        $this->actingAs($this->user('venta'))->get($url)->assertForbidden();
        $this->actingAs($admin)->get($url)->assertOk()->assertSee($recibo->folio);
        $this->actingAs($admin)->get($url.'&comprobante=999999')->assertForbidden();
    }

    public function test_firma_valida_no_permite_relacion_pago_comprobante_cruzada(): void
    {
        $admin = $this->user('admin');
        ['pago' => $a] = $this->pagoConfirmado($admin);
        ['pago' => $b] = $this->pagoConfirmado($admin);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($a, $admin->getKey());
        $cruzada = URL::signedRoute('facturacion.comprobantes.ver', ['pago' => $b, 'comprobante' => $recibo]);
        $this->actingAs($admin)->get($cruzada)->assertNotFound();
    }
}
