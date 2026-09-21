<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowPagos;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\ResponsablePago;
use App\Services\Facturacion\AplicarPagoService;
use App\Services\Facturacion\CancelarPagoService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\ArchivoPagoFixtures;

class ArchivoPagoDetalleCancelacionTest extends InscripcionesTestCase
{
    use ArchivoPagoFixtures;

    protected function tearDown(): void
    {
        $this->limpiarTemporalesArchivoPago();
        parent::tearDown();
    }

    public function test_detalle_escapa_nombre_muestra_metadatos_y_pago_historico_sin_adjuntos(): void
    {
        Storage::fake('local');
        $caso = $this->confirmarConArchivo('factura<script>alert(1)<script>.pdf');
        $archivo = $caso['pago']->archivos->sole();
        $component = Livewire::actingAs($caso['admin'])->test(ShowPagos::class)->call('verDetalle', $caso['pago']->getKey())
            ->assertSee('factura&lt;script&gt;alert(1)&lt;script&gt;.pdf', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee(number_format($archivo->tamano_bytes / 1024, 1).' KB')
            ->assertSee($archivo->created_at->format('Y-m-d H:i:s'))->assertSee($caso['admin']->name)
            ->assertSee(route('facturacion.pagos.archivos.descargar', [$caso['pago'], $archivo]), false);
        $this->assertStringNotContainsString('/storage/', $component->lastResponse->getContent());

        $historico = Pago::forceCreate(array_merge($caso['pago']->only(['inscripciones_id', 'prospectos_id', 'responsable_pago_id', 'metodo_pago_id']), [
            'folio' => 'PAG-HISTORICO', 'fecha_pago' => now(), 'zona_horaria' => 'UTC', 'moneda' => 'MXN', 'monto' => '1.00', 'estado' => Pago::ESTADO_CONFIRMADO,
        ]));
        Livewire::actingAs($caso['admin'])->test(ShowPagos::class)->call('verDetalle', $historico->getKey())
            ->assertSee('Este pago no tiene comprobantes adjuntos.');
    }

    public function test_cancelacion_real_conserva_metadatos_bytes_y_descarga_autorizada(): void
    {
        Storage::fake('local');
        $caso = $this->confirmarConArchivo('cancelable.pdf');
        $pago = $caso['pago']; $archivo = $pago->archivos->sole();
        $metadata = $archivo->getAttributes();
        $bytes = Storage::disk('local')->get($archivo->ruta);

        app(CancelarPagoService::class)->cancelar($pago->getKey(), 'Reverso bancario', $caso['admin']->getKey());

        $this->assertSame(Pago::ESTADO_CANCELADO, $pago->fresh()->estado);
        $this->assertSame(['10.00', Cargo::ESTADO_PENDIENTE], [$caso['cargo']->fresh()->saldo_pendiente, $caso['cargo']->fresh()->estado]);
        $this->assertSame($metadata, $archivo->fresh()->getAttributes());
        $this->assertSame($bytes, Storage::disk('local')->get($archivo->ruta));
        $response = $this->actingAs($caso['admin'])->get(route('facturacion.pagos.archivos.descargar', [$pago, $archivo]))->assertOk();
        $this->assertSame($bytes, $response->streamedContent());
        $this->assertSame('local', config('livewire.temporary_file_upload.disk'));
        $this->assertSame('livewire-tmp', config('livewire.temporary_file_upload.directory'));
        $this->assertNotSame('public', config('livewire.temporary_file_upload.disk'));
    }

    private function confirmarConArchivo(string $nombre): array
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $responsable = ResponsablePago::create(['tipo' => 'persona', 'prospectos_id' => $prospecto->getKey(), 'nombre_razon_social' => 'Responsable', 'activo' => true]);
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $inscripcion->update(['estatus' => 'activa', 'responsable_pago_id' => $responsable->getKey()]);
        $cargo = Cargo::create(['inscripciones_id' => $inscripcion->getKey(), 'concepto_cobro_id' => ConceptoCobro::first()->getKey(),
            'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-30', 'moneda' => 'MXN', 'subtotal' => '10.00',
            'total' => '10.00', 'saldo_pendiente' => '10.00', 'estado' => Cargo::ESTADO_PENDIENTE, 'origen' => Cargo::ORIGEN_MANUAL]);
        $admin = $this->user('admin');
        $metodo = MetodoPago::where('clave', MetodoPago::TRANSFERENCIA_SPEI)->firstOrFail();
        $pago = app(AplicarPagoService::class)->confirmar($inscripcion->getKey(), $metodo->getKey(), [
            'fecha_pago' => '2026-09-20 12:00:00', 'zona_horaria' => 'UTC', 'monto' => '10.00',
            'banco' => 'Banco', 'referencia' => uniqid('REF-'), 'rastreo_spei' => uniqid('SPEI-'),
            'comprobante' => $this->pdfValido($nombre),
        ], [$cargo->getKey() => '10.00'], $admin->getKey());
        return compact('pago', 'cargo', 'admin');
    }
}
