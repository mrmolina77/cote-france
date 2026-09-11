<?php

namespace Tests\Feature;

use App\Models\ArchivoPago;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Services\Facturacion\ArchivoPagoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ArchivoPagoTest extends InscripcionesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** @dataProvider archivosValidos */
    public function test_valida_contenido_real_y_persiste_en_ubicacion_privada(string $nombre, string $base64, string $mime): void
    {
        $pago = $this->pago();
        $archivo = $this->upload($nombre, base64_decode($base64));

        $guardado = app(ArchivoPagoService::class)->guardar($pago, $archivo, $this->user('admin')->getKey());

        $this->assertSame($mime, $guardado->mime_type);
        $this->assertStringStartsWith('archivos_pago/', $guardado->ruta);
        $this->assertStringNotContainsString($nombre, $guardado->ruta);
        Storage::disk('local')->assertExists($guardado->ruta);
        Storage::disk('public')->assertMissing($guardado->ruta);
        $this->assertTrue($pago->archivos->first()->is($guardado));
    }

    public static function archivosValidos(): array
    {
        return [
            'pdf' => ['recibo.pdf', base64_encode("%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF"), 'application/pdf'],
            'png' => ['recibo.png', 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', 'image/png'],
            'jpeg' => ['recibo.jpeg', '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAEf/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9k=', 'image/jpeg'],
        ];
    }

    /** @dataProvider archivosInvalidos */
    public function test_rechaza_archivos_vacios_contenido_falso_y_extension_incoherente(string $nombre, string $contenido): void
    {
        $this->expectException(ValidationException::class);
        app(ArchivoPagoService::class)->validar($this->upload($nombre, $contenido));
    }

    public static function archivosInvalidos(): array
    {
        return [
            'vacío' => ['vacio.pdf', ''],
            'ejecutable renombrado' => ['malware.pdf', "#!/bin/sh\necho hacked"],
            'extensión' => ['documento.exe', "%PDF-1.4\n%%EOF"],
        ];
    }

    public function test_nombres_repetidos_no_sobrescriben_y_nombre_malicioso_se_sanea(): void
    {
        $service = app(ArchivoPagoService::class);
        $pago = $this->pago();
        $usuario = $this->user('admin');
        $contenido = "%PDF-1.4\n%%EOF";
        $a = $service->guardar($pago, $this->upload("../malo\r\n.pdf", $contenido), $usuario->getKey());
        $b = $service->guardar($pago, $this->upload("../malo\r\n.pdf", $contenido), $usuario->getKey());

        $this->assertNotSame($a->ruta, $b->ruta);
        $this->assertStringNotContainsString('..', $a->nombre_original);
        $this->assertStringNotContainsString("\r", $a->nombre_original);
        $this->assertStringNotContainsString("\n", $a->nombre_original);
    }

    public function test_acepta_limite_exacto_y_rechaza_un_byte_adicional(): void
    {
        $cabecera = "%PDF-1.4\n";
        $limite = $cabecera.str_repeat('0', ArchivoPagoService::MAXIMO_BYTES - strlen($cabecera));
        $this->assertSame(ArchivoPagoService::MAXIMO_BYTES, app(ArchivoPagoService::class)
            ->validar($this->upload('limite.pdf', $limite))['tamano_bytes']);

        $this->expectException(ValidationException::class);
        app(ArchivoPagoService::class)->validar($this->upload('exceso.pdf', $limite.'0'));
    }

    public function test_fk_impide_borrar_pago_con_evidencia_y_usuario_es_nullable(): void
    {
        $usuario = $this->user('admin');
        $pago = $this->pago();
        $archivo = app(ArchivoPagoService::class)->guardar(
            $pago, $this->upload('evidencia.pdf', "%PDF-1.4\n%%EOF"), $usuario->getKey()
        );
        $usuario->delete();
        $this->assertNull($archivo->fresh()->created_by);

        $this->expectException(\Throwable::class);
        \Illuminate\Support\Facades\DB::table('pagos')->where('pago_id', $pago->getKey())->delete();
    }

    public function test_descarga_exige_autorizacion_pertenencia_y_bytes_existentes(): void
    {
        $admin = $this->user('admin');
        $pago = $this->pago();
        $otro = $this->pago();
        $archivo = app(ArchivoPagoService::class)->guardar(
            $pago, $this->upload('comprobante.pdf', "%PDF-1.4\n%%EOF"), $admin->getKey()
        );
        $url = route('facturacion.pagos.archivos.descargar', [$pago, $archivo]);

        $this->get($url)->assertRedirect('/login');
        $this->actingAs($this->user('ventas'))->get($url)->assertForbidden();
        $this->actingAs($admin)->get($url)->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('cache-control', 'private, no-store, max-age=0');
        $this->actingAs($admin)->get(route('facturacion.pagos.archivos.descargar', [$otro, $archivo]))->assertNotFound();
        Storage::disk('local')->delete($archivo->ruta);
        $this->actingAs($admin)->get($url)->assertNotFound();
    }

    public function test_descarga_rechaza_disco_y_ruta_no_permitidos(): void
    {
        $admin = $this->user('admin');
        $pago = $this->pago();
        foreach ([['public', 'archivos_pago/x.pdf'], ['local', '../x.pdf']] as [$disco, $ruta]) {
            $archivo = new ArchivoPago();
            $archivo->forceFill(['pago_id' => $pago->getKey(), 'nombre_original' => 'x.pdf', 'disco' => $disco,
                'ruta' => $ruta, 'mime_type' => 'application/pdf', 'tamano_bytes' => 10, 'created_by' => $admin->getKey()])->save();
            $this->actingAs($admin)->get(route('facturacion.pagos.archivos.descargar', [$pago, $archivo]))->assertNotFound();
        }
    }

    private function pago(): Pago
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $responsableId = \Illuminate\Support\Facades\DB::table('responsables_pago')->insertGetId([
            'tipo' => 'persona', 'nombre_razon_social' => 'Responsable', 'activo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $inscripcion->update(['responsable_pago_id' => $responsableId]);
        $pago = new Pago();
        $pago->forceFill([
            'folio' => uniqid('PAG-'), 'inscripciones_id' => $inscripcion->getKey(), 'prospectos_id' => $prospecto->getKey(),
            'responsable_pago_id' => $responsableId, 'fecha_pago' => now(), 'zona_horaria' => 'UTC', 'moneda' => 'MXN',
            'monto' => '10.00', 'metodo_pago_id' => MetodoPago::query()->firstOrFail()->getKey(), 'estado' => Pago::ESTADO_CONFIRMADO,
        ])->save();
        return $pago;
    }

    private function upload(string $nombre, string $contenido): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'archivo-pago-');
        file_put_contents($ruta, $contenido);
        return new UploadedFile($ruta, $nombre, null, UPLOAD_ERR_OK, true);
    }
}
