<?php

namespace Tests\Feature;

use App\Models\AuditoriaPago;
use App\Models\Cargo;
use App\Models\MetodoPago;
use App\Models\ResponsablePago;
use App\Services\Facturacion\AplicarPagoService;
use Illuminate\Support\Carbon;

class AuditoriaPagoIntegrationTest extends InscripcionesTestCase
{
    public function test_real_payment_confirmation_persists_the_complete_ordered_audit_contract(): void
    {
        Carbon::setTestNow('2026-09-23 12:00:00');
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $responsable = ResponsablePago::create([
            'tipo' => 'persona',
            'prospectos_id' => $prospecto->getKey(),
            'nombre_razon_social' => 'Responsable auditoría',
            'activo' => true,
        ]);
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $inscripcion->update([
            'estatus' => 'activa',
            'moneda' => 'MXN',
            'responsable_pago_id' => $responsable->getKey(),
        ]);
        $cargo = Cargo::create([
            'inscripciones_id' => $inscripcion->getKey(),
            'concepto_cobro_id' => 1,
            'fecha_emision' => '2026-09-01',
            'fecha_vencimiento' => '2026-09-30',
            'moneda' => 'MXN',
            'subtotal' => '25.40',
            'total' => '25.40',
            'saldo_pendiente' => '25.40',
            'estado' => Cargo::ESTADO_PENDIENTE,
            'origen' => Cargo::ORIGEN_MANUAL,
        ]);
        $usuario = $this->user('admin');
        $this->actingAs($usuario);
        request()->server->set('REMOTE_ADDR', '198.51.100.23');

        $pago = app(AplicarPagoService::class)->confirmar(
            $inscripcion->getKey(),
            MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail()->getKey(),
            [
                'fecha_pago' => '2026-09-23 10:00:00',
                'zona_horaria' => 'UTC',
                'monto' => '25.40',
                'observaciones' => 'dato fuera de la lista blanca',
            ],
            [$cargo->getKey() => '25.40'],
            $usuario->getKey()
        );

        $eventos = AuditoriaPago::where('pago_id', $pago->getKey())
            ->orderBy('auditoria_pago_id')->get();
        $this->assertSame([AuditoriaPago::CREAR, AuditoriaPago::CONFIRMAR], $eventos->pluck('accion')->all());
        $this->assertLessThan($eventos[1]->auditoria_pago_id, $eventos[0]->auditoria_pago_id);
        $this->assertSame([], $eventos[0]->valores_anteriores);
        $this->assertSame('inexistente', $eventos[1]->valores_anteriores['estado']);

        foreach ($eventos as $evento) {
            $this->assertSame($usuario->getKey(), $evento->usuario_id);
            $this->assertSame('198.51.100.23', $evento->ip_address);
            $this->assertSame('25.40', $evento->valores_nuevos['monto']);
            $this->assertTrue($evento->metadatos['operacion_atomica']);
            $this->assertSame('25.40', $evento->metadatos['aplicaciones'][0]['importe_aplicado']);
            $this->assertSame('25.40', $evento->metadatos['cargos'][0]['saldo_anterior']);
            $this->assertSame('0.00', $evento->metadatos['cargos'][0]['saldo_posterior']);
            $this->assertStringNotContainsString('observaciones', json_encode($evento->toArray()));
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
