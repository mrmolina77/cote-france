<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowPagos;
use App\Jobs\EnviarNotificacionPago;
use App\Services\Facturacion\GeneradorComprobantePagoService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

class ReenvioReciboTest extends ComprobantePagoTestCase
{
    public function test_admin_can_resend_same_receipt_and_unauthorized_role_cannot(): void
    {
        Storage::fake('local'); Queue::fake();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        Livewire::actingAs($admin)->test(ShowPagos::class)->call('reenviarRecibo', $pago->getKey())->assertHasNoErrors();
        $this->assertDatabaseHas('notificaciones_pago', ['pago_id' => $pago->getKey(), 'comprobante_pago_id' => $recibo->getKey(), 'solicitado_por' => $admin->getKey()]);
        $this->assertDatabaseCount('comprobantes_pago', 1); Queue::assertPushed(EnviarNotificacionPago::class, 1);
        Livewire::actingAs($this->user('venta'))->test(ShowPagos::class)->assertForbidden();
    }
}
