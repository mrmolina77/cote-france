<?php

namespace Tests\Feature;

use App\Models\NotificacionPago;
use App\Services\Facturacion\GeneradorComprobantePagoService;
use App\Services\Facturacion\NotificacionPagoService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class NotificacionesPagoMigrationTest extends ComprobantePagoTestCase
{
    public function test_schema_relations_and_unique_logical_event(): void
    {
        Storage::fake('local'); Queue::fake();
        foreach (['notificacion_pago_id', 'pago_id', 'comprobante_pago_id', 'tipo', 'tipo_solicitud',
            'clave_idempotencia', 'destinatario', 'estado', 'intentos', 'programado_en', 'iniciado_en',
            'enviado_en', 'ultimo_intento_en', 'ultimo_error', 'solicitado_por', 'solicitado_en',
            'mensaje_proveedor_id'] as $column) $this->assertTrue(Schema::hasColumn('notificaciones_pago', $column));
        $admin = $this->user('admin');
        ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => ' RESPONSABLE@EXAMPLE.COM ']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $first = app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo);
        $second = app(NotificacionPagoService::class)->solicitarInicialRecibido($pago, $recibo);
        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame('responsable@example.com', $first->destinatario);
        $this->assertTrue($first->pago->is($pago));
        $this->assertTrue($first->comprobantePago->is($recibo));
        $this->assertDatabaseCount('notificaciones_pago', 1);
    }
}
