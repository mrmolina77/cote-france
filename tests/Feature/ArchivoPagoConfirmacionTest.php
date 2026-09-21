<?php

namespace Tests\Feature;

use App\Models\ArchivoPago;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\ResponsablePago;
use App\Services\Facturacion\AplicarPagoService;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\Support\ArchivoPagoFixtures;

class ArchivoPagoConfirmacionTest extends InscripcionesTestCase
{
    use ArchivoPagoFixtures;

    private $inscripcion;
    private $cargo;
    private $usuario;
    private $metodo;
    private $disco;
    private array $evidencia;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->disco = Storage::disk('local');
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $responsable = ResponsablePago::create(['tipo' => 'persona', 'prospectos_id' => $prospecto->getKey(), 'nombre_razon_social' => 'Responsable', 'activo' => true]);
        $this->inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $this->inscripcion->update(['estatus' => 'activa', 'moneda' => 'MXN', 'responsable_pago_id' => $responsable->getKey()]);
        $this->cargo = Cargo::create(['inscripciones_id' => $this->inscripcion->getKey(), 'concepto_cobro_id' => ConceptoCobro::first()->getKey(),
            'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-30', 'moneda' => 'MXN', 'subtotal' => '10.00',
            'total' => '10.00', 'saldo_pendiente' => '10.00', 'estado' => Cargo::ESTADO_PENDIENTE, 'origen' => Cargo::ORIGEN_MANUAL]);
        $this->usuario = $this->user('admin');
        $this->metodo = MetodoPago::where('clave', MetodoPago::TRANSFERENCIA_SPEI)->firstOrFail();
        $previo = Pago::forceCreate($this->datosPagoPrevio($responsable->getKey(), $prospecto->getKey()));
        $this->disco->put('archivos_pago/previo.pdf', 'evidencia-anterior');
        $archivo = ArchivoPago::forceCreate(['pago_id' => $previo->getKey(), 'nombre_original' => 'previo.pdf', 'disco' => 'local',
            'ruta' => 'archivos_pago/previo.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 18, 'created_by' => $this->usuario->getKey()]);
        $this->evidencia = $archivo->only(['archivo_pago_id', 'pago_id', 'ruta', 'tamano_bytes']);
    }

    protected function tearDown(): void
    {
        $this->limpiarTemporalesArchivoPago();
        Event::forget(MessageLogged::class);
        ArchivoPago::flushEventListeners();
        parent::tearDown();
    }

    public function test_put_false_compensa_sin_persistir(): void { $this->probarFallo('put_false'); }
    public function test_escritura_parcial_real_se_elimina_y_revierte(): void { $this->probarFallo('partial'); }
    public function test_excepcion_en_exists_compensa_y_revierte(): void { $this->probarFallo('exists'); }
    public function test_error_insertando_metadatos_compensa_y_revierte(): void { $this->probarFallo('metadata'); }
    public function test_error_posterior_a_archivo_y_metadatos_compensa_y_revierte(): void { $this->probarFallo('after_metadata'); }

    /** @dataProvider comprobantesInvalidos */
    public function test_confirmar_revalida_extension_vacio_y_contenido_falso(string $nombre, string $contenido): void
    {
        try {
            $this->confirmar($this->archivoDesdeContenido($nombre, $contenido));
            $this->fail('El comprobante inválido debía rechazarse.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('comprobante', $error->errors());
        }
        $this->assertSinEfectosNuevos();
    }

    public static function comprobantesInvalidos(): array
    {
        return [
            'extensión incompatible' => ['comprobante.jpg', self::bytesFixtureArchivoPago('comprobante.pdf.base64')],
            'vacío' => ['comprobante.pdf', ''],
            'contenido falso' => ['comprobante.pdf', "%PDF-1.4\nfalso\n%%EOF\n"],
        ];
    }

    public function test_temporal_eliminado_antes_de_confirmar_se_rechaza_sin_efectos(): void
    {
        $archivo = $this->pdfValido();
        unlink($archivo->getRealPath());
        try { $this->confirmar($archivo); $this->fail('El temporal vencido debía rechazarse.'); }
        catch (ValidationException $error) { $this->assertArrayHasKey('comprobante', $error->errors()); }
        $this->assertSinEfectosNuevos();
    }

    public function test_confirmacion_valida_persiste_pertenencia_usuario_mime_tamano_bytes_aplicacion_y_saldo(): void
    {
        $bytes = self::bytesFixtureArchivoPago('comprobante.pdf.base64');
        $pago = $this->confirmar();
        $archivo = $pago->archivos->sole();
        $this->assertSame($pago->getKey(), (int) $archivo->pago_id);
        $this->assertSame($this->usuario->getKey(), (int) $archivo->created_by);
        $this->assertSame('application/pdf', $archivo->mime_type);
        $this->assertSame(strlen($bytes), (int) $archivo->tamano_bytes);
        $this->assertSame($bytes, $this->disco->get($archivo->ruta));
        $this->assertDatabaseHas('pago_aplicaciones', ['pago_id' => $pago->getKey(), 'cargo_id' => $this->cargo->getKey(), 'saldo_posterior' => '0.00']);
        $this->assertSame(['0.00', Cargo::ESTADO_PAGADO], [$this->cargo->fresh()->saldo_pendiente, $this->cargo->fresh()->estado]);
        $this->assertFalse(str_starts_with($archivo->ruta, 'livewire-tmp/'));
    }

    public function test_reenvio_del_estado_anterior_no_duplica_pago_aplicacion_metadato_ni_archivo(): void
    {
        $primero = $this->confirmar();
        $conteos = [Pago::count(), DB::table('pago_aplicaciones')->count(), ArchivoPago::count()];
        $archivos = $this->disco->allFiles('archivos_pago');
        try { $this->confirmar(); $this->fail('El cargo ya pagado debía impedir el reenvío.'); }
        catch (ValidationException $error) { $this->assertArrayHasKey('aplicaciones', $error->errors()); }
        $this->assertSame($conteos, [Pago::count(), DB::table('pago_aplicaciones')->count(), ArchivoPago::count()]);
        $this->assertSame($archivos, $this->disco->allFiles('archivos_pago'));
        $this->assertCount(1, $primero->archivos);
        $this->assertSame([], $this->disco->allFiles('livewire-tmp'));
    }

    public function test_error_de_limpieza_se_reporta_conserva_excepcion_original_y_deja_residuo(): void
    {
        $mensajes = [];
        Event::listen(MessageLogged::class, function (MessageLogged $evento) use (&$mensajes): void { $mensajes[] = $evento->message; });
        $errorOriginal = new RuntimeException('fallo original de metadatos');
        ArchivoPago::creating(function () use ($errorOriginal): void { throw $errorOriginal; });
        $mock = \Mockery::mock($this->disco)->makePartial();
        $mock->shouldReceive('delete')->once()->andThrow(new RuntimeException('fallo de borrado'));
        Storage::shouldReceive('disk')->with('local')->andReturn($mock);

        try {
            $this->confirmar();
            $this->fail('La confirmación debía fallar.');
        } catch (RuntimeException $error) {
            $this->assertSame($errorOriginal, $error, 'La compensación no debe reemplazar la causa original.');
        }

        $this->assertTrue(collect($mensajes)->contains(fn ($mensaje) => str_contains($mensaje, 'Falló la compensación')));
        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseCount('pago_aplicaciones', 0);
        $this->assertDatabaseCount('archivos_pago', 1);
        $this->assertSame('10.00', $this->cargo->fresh()->saldo_pendiente);
        $nuevos = array_values(array_diff($this->disco->allFiles('archivos_pago'), ['archivos_pago/previo.pdf']));
        $this->assertCount(1, $nuevos, 'El residuo es esperado y queda registrado cuando la compensación falla.');
        $this->assertControlIntacto();
    }

    private function probarFallo(string $modo): void
    {
        $error = new RuntimeException('fallo '.$modo);
        $mock = \Mockery::mock($this->disco)->makePartial();
        if ($modo === 'put_false') {
            $mock->shouldReceive('put')->once()->andReturn(false);
        } elseif ($modo === 'partial') {
            $real = $this->disco;
            $mock->shouldReceive('put')->once()->andReturnUsing(function ($ruta) use ($real) { $real->put($ruta, 'bytes-parciales'); return false; });
        } elseif ($modo === 'exists') {
            $mock->shouldReceive('exists')->once()->andThrow($error);
        } elseif ($modo === 'metadata') {
            ArchivoPago::creating(function () use ($error): void { throw $error; });
        } else {
            ArchivoPago::created(function () use ($error): void { throw $error; });
        }
        Storage::shouldReceive('disk')->with('local')->andReturn($mock);

        try { $this->confirmar(); $this->fail('La confirmación debía fallar.'); } catch (RuntimeException $capturado) {
            if (in_array($modo, ['exists', 'metadata', 'after_metadata'], true)) $this->assertSame($error, $capturado);
        }

        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseCount('pago_aplicaciones', 0);
        $this->assertDatabaseCount('archivos_pago', 1);
        $this->assertSame(['10.00', Cargo::ESTADO_PENDIENTE], [$this->cargo->fresh()->saldo_pendiente, $this->cargo->fresh()->estado]);
        $this->assertSame(['archivos_pago/previo.pdf'], $this->disco->allFiles('archivos_pago'));
        $this->assertControlIntacto();
    }

    private function confirmar($comprobante = null): Pago
    {
        return app(AplicarPagoService::class)->confirmar($this->inscripcion->getKey(), $this->metodo->getKey(), [
            'fecha_pago' => '2026-09-20 12:00:00', 'zona_horaria' => 'UTC', 'monto' => '10.00',
            'banco' => 'Banco', 'referencia' => 'REF-EPIC9', 'rastreo_spei' => 'SPEI-EPIC9',
            'comprobante' => $comprobante ?? $this->pdfValido(),
        ], [$this->cargo->getKey() => '10.00'], $this->usuario->getKey());
    }

    private function assertSinEfectosNuevos(): void
    {
        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseCount('pago_aplicaciones', 0);
        $this->assertDatabaseCount('archivos_pago', 1);
        $this->assertSame('10.00', $this->cargo->fresh()->saldo_pendiente);
        $this->assertSame(['archivos_pago/previo.pdf'], $this->disco->allFiles('archivos_pago'));
        $this->assertControlIntacto();
    }

    private function assertControlIntacto(): void
    {
        $this->assertDatabaseHas('archivos_pago', $this->evidencia);
        $this->assertSame('evidencia-anterior', $this->disco->get('archivos_pago/previo.pdf'));
    }

    private function datosPagoPrevio(int $responsable, int $prospecto): array
    {
        return ['folio' => 'PAG-2026-CONTROL', 'inscripciones_id' => $this->inscripcion->getKey(), 'prospectos_id' => $prospecto,
            'responsable_pago_id' => $responsable, 'fecha_pago' => now(), 'zona_horaria' => 'UTC', 'moneda' => 'MXN',
            'monto' => '1.00', 'metodo_pago_id' => $this->metodo->getKey(), 'estado' => Pago::ESTADO_CONFIRMADO,
            'created_by' => $this->usuario->getKey(), 'confirmed_by' => $this->usuario->getKey(), 'fecha_confirmacion' => now()];
    }
}
