<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowPagos;
use App\Jobs\EnviarNotificacionPago;
use App\Services\Facturacion\GeneradorComprobantePagoService;
use App\Services\Facturacion\NotificacionPagoService;
use App\Models\NotificacionPago;
use Illuminate\Support\Facades\Notification;
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

    public function test_service_rejects_unauthorized_requester_and_receipt_from_another_payment(): void
    {
        Storage::fake('local'); Queue::fake();
        $admin = $this->user('admin'); $venta = $this->user('venta');
        ['pago' => $pago] = $this->pagoConfirmado($admin); ['pago' => $otro] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $otro->responsablePago->update(['correo' => 'other@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $reciboOtro = app(GeneradorComprobantePagoService::class)->generar($otro, $admin->getKey());
        $servicio = app(NotificacionPagoService::class);

        try {
            $servicio->solicitarReenvio($pago, $recibo, $venta->getKey(), 'unauthorized');
            $this->fail('Un usuario sin permiso no debe solicitar reenvíos.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame('No está autorizado para programar el envío.', $e->errors()['recibo'][0]);
        }
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $servicio->solicitarReenvio($pago, $reciboOtro, $admin->getKey(), 'idor');
    }

    public function test_job_omits_resend_when_requester_loses_permission(): void
    {
        Storage::fake('local'); Queue::fake(); Notification::fake();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $entrega = app(NotificacionPagoService::class)->solicitarReenvio($pago, $recibo, $admin->getKey(), 'lost-permission');
        $admin->role()->associate(\App\Models\Role::query()->where('roles_codigo', 'venta')->firstOrFail())->save();

        (new EnviarNotificacionPago($entrega->getKey()))->handle(app(NotificacionPagoService::class));

        $this->assertSame(NotificacionPago::ESTADO_OMITIDO, $entrega->fresh()->estado);
        Notification::assertNothingSent();
    }
}
