<?php

namespace Tests\Feature;

use App\Models\ArchivoPago;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Services\Facturacion\ArchivoPagoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\ArchivoPagoFixtures;

class ArchivoPagoTest extends InscripcionesTestCase
{
    use ArchivoPagoFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        $this->limpiarTemporalesArchivoPago();
        parent::tearDown();
    }

    /** @dataProvider archivosValidos */
    public function test_valida_contenido_real_y_persiste_en_ubicacion_privada(string $nombre, string $fixture, string $mime): void
    {
        $pago = $this->pago();
        $archivo = $this->archivoFixture($fixture, $nombre);

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
            'pdf' => ['recibo.pdf', 'comprobante.pdf.base64', 'application/pdf'],
            'png' => ['recibo.png', 'comprobante.png.base64', 'image/png'],
            'jpeg' => ['recibo.jpeg', 'comprobante.jpeg.base64', 'image/jpeg'],
        ];
    }

    /** @dataProvider fixturesImagen */
    public function test_fixture_de_imagen_se_decodifica_completo_con_gd(string $fixture, string $funcion): void
    {
        $archivo = $this->archivoFixture($fixture);
        $imagen = $funcion($archivo->getRealPath());
        $this->assertNotFalse($imagen, 'GD debe poder decodificar todos los bytes de '.$fixture);
        $this->assertSame(2, imagesx($imagen));
        $this->assertSame(2, imagesy($imagen));
        imagedestroy($imagen);
    }

    public static function fixturesImagen(): array
    {
        return [['comprobante.png.base64', 'imagecreatefrompng'], ['comprobante.jpeg.base64', 'imagecreatefromjpeg']];
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
        $contenido = self::bytesFixtureArchivoPago('comprobante.pdf.base64');
        $a = $service->guardar($pago, $this->upload("../malo\r\n.pdf", $contenido), $usuario->getKey());
        $b = $service->guardar($pago, $this->upload("../malo\r\n.pdf", $contenido), $usuario->getKey());

        $this->assertNotSame($a->ruta, $b->ruta);
        $this->assertStringNotContainsString('..', $a->nombre_original);
        $this->assertStringNotContainsString("\r", $a->nombre_original);
        $this->assertStringNotContainsString("\n", $a->nombre_original);
        $this->assertSame($contenido, Storage::disk('local')->get($a->ruta));
        $this->assertSame($contenido, Storage::disk('local')->get($b->ruta));
    }

    /** @dataProvider nombresSeguros */
    public function test_conserva_nombres_reconocibles_unicode_y_extension_al_sanear(string $original, string $esperado): void
    {
        $archivo = app(ArchivoPagoService::class)->guardar(
            $this->pago(), $this->archivoFixture('comprobante.pdf.base64', $original), $this->user('admin')->getKey()
        );

        $this->assertSame($esperado, $archivo->nombre_original);
        $this->assertLessThanOrEqual(240, mb_strlen($archivo->nombre_original));
    }

    public static function nombresSeguros(): array
    {
        return [
            'normal' => ['Estado de cuenta septiembre.pdf', 'Estado de cuenta septiembre.pdf'],
            'unicode' => ['Comprobante José_日本.pdf', 'Comprobante José_日本.pdf'],
            'controles y traversal' => ["../carpeta\\mal\0\r\n.pdf", '__carpeta_mal_.pdf'],
            'largo conserva extensión' => [str_repeat('á', 300).'.pdf', str_repeat('á', 118).'.pdf'],
        ];
    }

    public function test_rechaza_nombre_sin_extension_antes_de_sanear_y_no_persiste(): void
    {
        try {
            app(ArchivoPagoService::class)->guardar(
                $this->pago(), $this->archivoFixture('comprobante.pdf.base64', "\0\r\n"), $this->user('admin')->getKey()
            );
            $this->fail('El nombre sin una extensión permitida debió rechazarse.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('comprobante', $error->errors());
        }
        $this->assertDatabaseCount('archivos_pago', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('archivos_pago'));
    }

    public function test_rechaza_pdf_con_cabecera_pero_sin_estructura(): void
    {
        $this->expectException(ValidationException::class);
        app(ArchivoPagoService::class)->validar($this->upload('falso.pdf', "%PDF-1.4\ncontenido inventado\n%%EOF\n"));
    }

    public function test_nombre_unicode_largo_respeta_limite_en_bytes_y_extension(): void
    {
        $archivo = app(ArchivoPagoService::class)->guardar(
            $this->pago(), $this->archivoFixture('comprobante.pdf.base64', str_repeat('🧾', 100).'.pdf'),
            $this->user('admin')->getKey()
        );

        $this->assertLessThanOrEqual(240, strlen($archivo->nombre_original));
        $this->assertStringEndsWith('.pdf', $archivo->nombre_original);
        $this->assertSame(1, preg_match('//u', $archivo->nombre_original));
    }

    public function test_acepta_pdf_valido_del_limite_exacto_con_cierre_al_final(): void
    {
        $pdf = $this->pdfConTamanoExacto(ArchivoPagoService::MAXIMO_BYTES);
        $archivo = $this->archivoDesdeContenido('limite.pdf', $pdf);

        $this->assertSame(ArchivoPagoService::MAXIMO_BYTES, filesize($archivo->getRealPath()));
        $this->assertStringEndsWith("%%EOF\n", $pdf);
        $this->assertSame(ArchivoPagoService::MAXIMO_BYTES, app(ArchivoPagoService::class)->validar($archivo)['tamano_bytes']);
    }

    public function test_rechaza_pdf_valido_de_un_byte_sobre_el_limite_por_tamano(): void
    {
        $pdf = $this->pdfConTamanoExacto(ArchivoPagoService::MAXIMO_BYTES + 1);
        $archivo = $this->archivoDesdeContenido('exceso.pdf', $pdf);
        $this->assertSame(ArchivoPagoService::MAXIMO_BYTES + 1, filesize($archivo->getRealPath()));
        $this->assertStringEndsWith("%%EOF\n", $pdf);

        $this->expectException(ValidationException::class);
        app(ArchivoPagoService::class)->validar($archivo);
    }

    public function test_fk_impide_borrar_pago_con_evidencia_y_usuario_es_nullable(): void
    {
        $usuario = $this->user('admin');
        $pago = $this->pago();
        $archivo = app(ArchivoPagoService::class)->guardar(
            $pago, $this->archivoFixture('comprobante.pdf.base64', 'evidencia.pdf'), $usuario->getKey()
        );
        $usuario->delete();
        $this->assertNull($archivo->fresh()->created_by);

        try {
            \Illuminate\Support\Facades\DB::table('pagos')->where('pago_id', $pago->getKey())->delete();
            $this->fail('La FK debió impedir eliminar el pago con evidencia.');
        } catch (\Illuminate\Database\QueryException $error) {
            $this->assertNotSame('', $error->getMessage());
        }
        $this->assertDatabaseHas('pagos', ['pago_id' => $pago->getKey()]);
        $this->assertDatabaseHas('archivos_pago', ['archivo_pago_id' => $archivo->getKey()]);
        Storage::disk('local')->assertExists($archivo->ruta);
    }

    public function test_descarga_exige_autorizacion_pertenencia_y_bytes_existentes(): void
    {
        $admin = $this->user('admin');
        $pago = $this->pago();
        $otro = $this->pago();
        $archivo = app(ArchivoPagoService::class)->guardar(
            $pago, $this->archivoFixture('comprobante.pdf.base64'), $admin->getKey()
        );
        $url = route('facturacion.pagos.archivos.descargar', [$pago, $archivo]);

        $this->get($url)->assertRedirect('/login');
        $this->actingAs(\App\Models\User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs($this->user('ventas'))->get($url)->assertForbidden();
        $response = $this->actingAs($admin)->get($url)->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff');
        $cache = $response->headers->getCacheControlDirective('private');
        $this->assertTrue($cache);
        $this->assertTrue($response->headers->getCacheControlDirective('no-store'));
        $this->assertSame('0', (string) $response->headers->getCacheControlDirective('max-age'));
        $this->assertFalse($response->headers->hasCacheControlDirective('public'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('comprobante.pdf', $response->headers->get('content-disposition'));
        $this->assertSame(self::bytesFixtureArchivoPago('comprobante.pdf.base64'), $response->streamedContent());
        $this->actingAs($admin)->get(route('facturacion.pagos.archivos.descargar', [$otro, $archivo]))->assertNotFound();
        $this->actingAs($admin)->get(route('facturacion.pagos.archivos.descargar', [$pago->getKey(), 999999]))->assertNotFound();
        Storage::disk('local')->delete($archivo->ruta);
        $this->actingAs($admin)->get($url)->assertNotFound();
    }

    public function test_fixture_pdf_tiene_xref_offsets_y_cierre_coherentes(): void
    {
        $pdf = self::bytesFixtureArchivoPago('comprobante.pdf.base64');
        $this->assertStringStartsWith("%PDF-", $pdf);
        $this->assertStringEndsWith("%%EOF\n", $pdf);
        $this->assertSame(1, preg_match('/startxref\s+(\d+)\s+%%EOF\s*$/D', $pdf, $match));
        $xref = (int) $match[1];
        $this->assertSame('xref', substr($pdf, $xref, 4));
        $this->assertSame(1, preg_match('/xref\s+0\s+(\d+)\s+0000000000 65535 f\s+((?:\d{10} 00000 n\s+)+)/A', substr($pdf, $xref), $tabla));
        $offsets = preg_split('/\s+/', trim($tabla[2]));
        for ($objeto = 1; $objeto < (int) $tabla[1]; $objeto++) {
            $offset = (int) $offsets[($objeto - 1) * 3];
            $this->assertSame("{$objeto} 0 obj", substr($pdf, $offset, strlen("{$objeto} 0 obj")));
        }
    }

    public function test_descarga_rechaza_disco_y_ruta_no_permitidos(): void
    {
        $admin = $this->user('admin');
        $pago = $this->pago();
        foreach ([['public', 'archivos_pago/x.pdf'], ['local', '../x.pdf'], ['local', '/archivos_pago/x.pdf'], ['local', 'archivos_pago/../otro.pdf']] as [$disco, $ruta]) {
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
        return $this->archivoDesdeContenido($nombre, $contenido);
    }
}
