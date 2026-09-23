<?php

namespace Tests\Feature;

use App\Models\AuditoriaPago;
use App\Models\ComprobantePago;
use App\Models\Pago;
use App\Services\Facturacion\GeneradorComprobantePagoService;
use App\Services\Facturacion\RenderizadorPdfComprobanteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use RuntimeException;

class GeneradorComprobantePagoServiceTest extends ComprobantePagoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-21 12:00:00');
        Storage::fake('local');
    }

    public function test_genera_pdf_real_hash_qr_y_no_altera_datos_financieros(): void
    {
        $admin = $this->user('admin');
        ['pago' => $pago, 'cargos' => $cargos] = $this->pagoConfirmado($admin);
        $antes = $this->fotografia($pago);

        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $bytes = Storage::disk('local')->get($recibo->ruta_pdf);

        $this->assertDatabaseCount('comprobantes_pago', 1);
        $this->assertSame('REC-2026-000001', $recibo->folio);
        $this->assertSame($pago->getKey(), $recibo->pago_id);
        $this->assertSame(2026, $recibo->anio_folio);
        $this->assertSame(1, $recibo->secuencia_folio);
        $this->assertStringStartsWith('comprobantes_pago/', $recibo->ruta_pdf);
        $this->assertStringNotContainsString('..', $recibo->ruta_pdf);
        $this->assertSame(ComprobantePago::MIME_PDF, $recibo->mime_type);
        $this->assertSame(strlen($bytes), $recibo->tamano_bytes);
        $this->assertSame(hash('sha256', $bytes), $recibo->hash_sha256);
        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertStringEndsWith("%%EOF\n", $bytes);
        $c = fn (string $t) => iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $t);
        foreach (['Comprobante interno de pago. Este documento no constituye un CFDI.', $c('Élodie Martin'),
            $c('Inscripción: #'.$pago->inscripciones_id), 'Importe recibido: MXN $15.00', $c('Método de pago:'),
            'REC-2026-000001', $c('Inscripción'), 'Mensualidad', $c('Código QR interno:'), '/recibos/', 'q 0 0 0 rg'] as $texto) {
            $this->assertStringContainsString($texto, $bytes);
        }
        $this->assertSame(1, substr_count($bytes, 'Importe recibido: MXN $15.00'));
        preg_match('/startxref\s+(\d+)\s+%%EOF/s', $bytes, $xref);
        $this->assertNotEmpty($xref);
        $this->assertSame('xref', substr($bytes, (int) $xref[1], 4));
        $this->assertSame($antes, $this->fotografia($pago));
        $this->assertSame(['0.00', '0.00'], array_map(fn ($cargo) => $cargo->fresh()->saldo_pendiente, $cargos));

        $auditoria = AuditoriaPago::where('pago_id', $pago->getKey())
            ->where('accion', AuditoriaPago::GENERAR_RECIBO)->sole();
        $this->assertSame([], $auditoria->valores_anteriores);
        $this->assertSame($recibo->getKey(), $auditoria->valores_nuevos['comprobante_pago_id']);
        $this->assertSame($recibo->folio, $auditoria->valores_nuevos['folio']);
        $this->assertSame($recibo->hash_sha256, $auditoria->valores_nuevos['hash_sha256']);
        $this->assertSame($recibo->tamano_bytes, $auditoria->valores_nuevos['tamano_bytes']);
        $this->assertArrayHasKey('generado_en', $auditoria->valores_nuevos);
        $this->assertStringNotContainsString('ruta_pdf', json_encode($auditoria->toArray()));
    }

    public function test_estados_no_permitidos_no_dejan_folios_archivos_ni_registros(): void
    {
        $admin = $this->user('admin');
        foreach ([Pago::ESTADO_BORRADOR, Pago::ESTADO_CANCELADO, Pago::ESTADO_REEMBOLSADO] as $estado) {
            ['pago' => $pago] = $this->pagoConfirmado($admin);
            $pago->forceFill(['estado' => $estado])->save();
            try {
                app(GeneradorComprobantePagoService::class)->generar($pago);
                $this->fail('El estado '.$estado.' debió rechazarse.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('pago', $e->errors());
            }
        }
        $this->assertDatabaseCount('comprobantes_pago', 0);
        $this->assertDatabaseCount('consecutivos_comprobante_pago', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('comprobantes_pago'));
    }

    public function test_regeneracion_es_idempotente_y_un_fallo_conserva_pdf_anterior(): void
    {
        $admin = $this->user('admin');
        ['pago' => $pago] = $this->pagoConfirmado($admin);
        $servicio = app(GeneradorComprobantePagoService::class);
        $primero = $servicio->generar($pago, $admin->getKey());
        $ruta = $primero->ruta_pdf;
        $bytes = Storage::disk('local')->get($ruta);
        $pago->update(['observaciones' => 'Presentación actualizada']);
        $segundo = $servicio->generar($pago->fresh(), $admin->getKey());
        $this->assertSame($primero->getKey(), $segundo->getKey());
        $this->assertSame($primero->folio, $segundo->folio);
        $this->assertSame(1, DB::table('consecutivos_comprobante_pago')->value('ultimo_consecutivo'));
        $this->assertNotSame($bytes, Storage::disk('local')->get($ruta));
        $auditorias = AuditoriaPago::where('pago_id', $pago->getKey())
            ->whereIn('accion', [AuditoriaPago::GENERAR_RECIBO, AuditoriaPago::REGENERAR_RECIBO])
            ->orderBy('auditoria_pago_id')->get();
        $this->assertSame([AuditoriaPago::GENERAR_RECIBO, AuditoriaPago::REGENERAR_RECIBO], $auditorias->pluck('accion')->all());
        $this->assertSame($primero->getKey(), $auditorias[1]->valores_anteriores['comprobante_pago_id']);
        $this->assertSame($segundo->getKey(), $auditorias[1]->valores_nuevos['comprobante_pago_id']);
        $this->assertNotSame($auditorias[1]->valores_anteriores['hash_sha256'], $auditorias[1]->valores_nuevos['hash_sha256']);
        $estable = Storage::disk('local')->get($ruta);
        $metadatos = $segundo->only(['ruta_pdf', 'hash_sha256', 'tamano_bytes', 'generado_en']);

        $fallido = \Mockery::mock(RenderizadorPdfComprobanteService::class);
        $fallido->shouldReceive('renderizar')->once()->andThrow(new RuntimeException('fallo controlado'));
        $this->app->instance(RenderizadorPdfComprobanteService::class, $fallido);
        $this->expectException(RuntimeException::class);
        try {
            app(GeneradorComprobantePagoService::class)->generar($pago->fresh(), $admin->getKey());
        } finally {
            $this->assertSame($estable, Storage::disk('local')->get($ruta));
            $this->assertEquals($metadatos, $segundo->fresh()->only(array_keys($metadatos)));
            $this->assertDatabaseCount('comprobantes_pago', 1);
            $this->assertSame([], Storage::disk('local')->allFiles('comprobantes_pago/temporales'));
            $this->assertSame(1, AuditoriaPago::where('pago_id', $pago->getKey())
                ->where('accion', AuditoriaPago::REGENERAR_RECIBO)->count());
        }
    }

    public function test_fallo_del_renderizador_no_consume_folio_ni_deja_registro_o_temporal(): void
    {
        $admin = $this->user('admin');
        ['pago' => $pago] = $this->pagoConfirmado($admin);
        $fallido = \Mockery::mock(RenderizadorPdfComprobanteService::class);
        $fallido->shouldReceive('renderizar')->once()->andThrow(new RuntimeException('render fallido'));
        $this->app->instance(RenderizadorPdfComprobanteService::class, $fallido);

        try {
            app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
            $this->fail('La generación debió fallar.');
        } catch (RuntimeException $e) {
            $this->assertSame('render fallido', $e->getMessage());
        }
        $this->assertDatabaseCount('comprobantes_pago', 0);
        $this->assertDatabaseCount('consecutivos_comprobante_pago', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('comprobantes_pago'));
        $this->assertSame(0, AuditoriaPago::where('pago_id', $pago->getKey())
            ->whereIn('accion', [AuditoriaPago::GENERAR_RECIBO, AuditoriaPago::REGENERAR_RECIBO])->count());
    }

    public function test_recibo_existente_sigue_disponible_si_el_pago_se_cancela(): void
    {
        $admin = $this->user('admin');
        ['pago' => $pago] = $this->pagoConfirmado($admin);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $pago->forceFill(['estado' => Pago::ESTADO_CANCELADO])->save();

        $mismo = app(GeneradorComprobantePagoService::class)->generar($pago->fresh(), $admin->getKey());
        $this->assertSame($recibo->getKey(), $mismo->getKey());
        $this->assertSame($recibo->folio, $mismo->folio);
        Storage::disk('local')->assertExists($mismo->ruta_pdf);
    }

    private function fotografia(Pago $pago): array
    {
        return [
            (array) DB::table('pagos')->where('pago_id', $pago->getKey())->first(),
            DB::table('pago_aplicaciones')->where('pago_id', $pago->getKey())->orderBy('pago_aplicacion_id')->get()->map(fn ($v) => (array) $v)->all(),
            DB::table('cargos')->whereIn('cargo_id', $pago->aplicaciones()->pluck('cargo_id'))->orderBy('cargo_id')->get()->map(fn ($v) => (array) $v)->all(),
        ];
    }
}
