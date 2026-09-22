<?php

namespace Tests\Feature;

use App\Services\Facturacion\GeneradorComprobantePagoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class DescargarComprobantePagoTest extends ComprobantePagoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-21 12:00:00');
        Storage::fake('local');
    }

    public function test_descarga_privada_autorizada_tiene_headers_y_bytes_exactos(): void
    {
        $admin = $this->user('admin');
        ['pago' => $pago] = $this->pagoConfirmado($admin);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $url = route('facturacion.comprobantes.descargar', ['pago' => $pago, 'comprobante' => $recibo]);

        $this->get($url)->assertRedirect('/login');
        $this->actingAs($this->user('venta'))->get($url)->assertForbidden();
        $respuesta = $this->actingAs($admin)->get($url)->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('attachment;', $respuesta->headers->get('Content-Disposition'));
        $this->assertStringContainsString('private', $respuesta->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $respuesta->headers->get('Cache-Control'));
        $this->assertSame(Storage::disk('local')->get($recibo->ruta_pdf), $respuesta->getContent());
        $this->actingAs($admin)->get('/storage/'.$recibo->ruta_pdf)->assertNotFound();
    }

    public function test_ids_cruzados_y_archivo_alterado_no_se_descargan(): void
    {
        $admin = $this->user('admin');
        ['pago' => $pagoA] = $this->pagoConfirmado($admin);
        ['pago' => $pagoB] = $this->pagoConfirmado($admin);
        $a = app(GeneradorComprobantePagoService::class)->generar($pagoA, $admin->getKey());
        $b = app(GeneradorComprobantePagoService::class)->generar($pagoB, $admin->getKey());
        $this->actingAs($admin)
            ->get(route('facturacion.comprobantes.descargar', ['pago' => $pagoA, 'comprobante' => $b]))->assertNotFound();
        $this->actingAs($admin)
            ->get(route('facturacion.comprobantes.descargar', ['pago' => $pagoB, 'comprobante' => $a]))->assertNotFound();
        Storage::disk('local')->put($a->ruta_pdf, 'alterado');
        $this->actingAs($admin)
            ->get(route('facturacion.comprobantes.descargar', ['pago' => $pagoA, 'comprobante' => $a]))->assertNotFound();
    }
}
