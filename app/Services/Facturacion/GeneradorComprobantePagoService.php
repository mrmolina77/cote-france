<?php

namespace App\Services\Facturacion;

use App\Models\ComprobantePago;
use App\Models\Pago;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GeneradorComprobantePagoService
{
    public function __construct(
        private GeneradorFolioComprobanteService $folios,
        private ImporteEnLetrasService $importeEnLetras,
        private RenderizadorPdfComprobanteService $pdf
    ) {}

    public function generar(Pago $pago, ?int $usuarioId = null): ComprobantePago
    {
        $rutaTemporal = null; $respaldo = null; $rutaFinal = null;
        try {
            return DB::transaction(function () use ($pago, $usuarioId, &$rutaTemporal, &$respaldo, &$rutaFinal) {
                $pago = Pago::query()->whereKey($pago->getKey())->lockForUpdate()->firstOrFail();
                $comprobante = ComprobantePago::query()->where('pago_id', $pago->getKey())->lockForUpdate()->first();
                if (! $comprobante && $pago->estado !== Pago::ESTADO_CONFIRMADO) {
                    throw ValidationException::withMessages(['pago' => 'Sólo un pago confirmado puede generar un recibo interno.']);
                }
                $ahora = now()->setTimezone(config('app.timezone'));
                if (! $comprobante) {
                    $folio = $this->folios->generar($ahora);
                    $comprobante = new ComprobantePago();
                    $comprobante->forceFill([
                        'pago_id'=>$pago->getKey(), 'folio'=>$folio['folio'], 'anio_folio'=>$folio['anio'],
                        'secuencia_folio'=>$folio['secuencia'], 'disco'=>ComprobantePago::DISCO_PRIVADO,
                        'ruta_pdf'=>ComprobantePago::DIRECTORIO.'/pendiente/'.$folio['folio'].'.pdf',
                        'hash_sha256'=>str_repeat('0',64), 'mime_type'=>ComprobantePago::MIME_PDF,
                        'tamano_bytes'=>0, 'generado_en'=>$ahora, 'generado_por'=>$usuarioId,
                    ])->save();
                }
                $pago->load([
                    'prospecto', 'responsablePago', 'metodoPago', 'confirmedBy',
                    'inscripcion.cursos', 'inscripcion.grupo', 'aplicaciones.cargo.conceptoCobro',
                ]);
                $comprobante->generado_en = $ahora;
                $urlQr = URL::signedRoute('facturacion.comprobantes.ver', [
                    'pago'=>$pago->getKey(), 'comprobante'=>$comprobante->getKey(),
                ]);
                $html = view('pdf.comprobante-pago', [
                    'pago'=>$pago, 'comprobante'=>$comprobante, 'urlQr'=>$urlQr,
                    'importeLetras'=>$this->importeEnLetras->convertir((string) $pago->monto, (string) $pago->moneda),
                    'hashPresentacion'=>substr(hash('sha256', $comprobante->folio.'|'.$pago->getKey()), 0, 16),
                    'config'=>config('comprobantes'),
                ])->render();
                $bytes = $this->pdf->renderizar($html, $urlQr);
                if (! str_starts_with($bytes, '%PDF-') || ! str_ends_with($bytes, "%%EOF\n")) throw new RuntimeException('No fue posible generar un PDF válido.');

                $rutaFinal = ComprobantePago::DIRECTORIO.'/'.$ahora->format('Y/m').'/'.$comprobante->folio.'.pdf';
                $rutaTemporal = ComprobantePago::DIRECTORIO.'/temporales/'.bin2hex(random_bytes(20)).'.pdf';
                $disco = Storage::disk(ComprobantePago::DISCO_PRIVADO);
                if ($disco->exists($rutaFinal)) $respaldo = $disco->get($rutaFinal);
                if (! $disco->put($rutaTemporal, $bytes, ['visibility'=>'private']) || ! $disco->exists($rutaTemporal)) {
                    throw new RuntimeException('No fue posible guardar el recibo temporal privado.');
                }
                if (! $disco->move($rutaTemporal, $rutaFinal)) throw new RuntimeException('No fue posible publicar el recibo privado.');
                $rutaTemporal = null;
                $comprobante->forceFill([
                    'disco'=>ComprobantePago::DISCO_PRIVADO, 'ruta_pdf'=>$rutaFinal,
                    'hash_sha256'=>hash('sha256', $bytes), 'mime_type'=>ComprobantePago::MIME_PDF,
                    'tamano_bytes'=>strlen($bytes), 'generado_en'=>$ahora, 'generado_por'=>$usuarioId,
                ])->save();
                return $comprobante->fresh(['pago']);
            }, 3);
        } catch (\Throwable $e) {
            $disco = Storage::disk(ComprobantePago::DISCO_PRIVADO);
            if ($rutaTemporal && $this->rutaPermitida($rutaTemporal)) $disco->delete($rutaTemporal);
            if ($rutaFinal && $this->rutaPermitida($rutaFinal)) {
                if (is_string($respaldo)) $disco->put($rutaFinal, $respaldo, ['visibility'=>'private']);
                else $disco->delete($rutaFinal);
            }
            throw $e;
        }
    }

    public function rutaPermitida(string $ruta): bool
    {
        return str_starts_with($ruta, ComprobantePago::DIRECTORIO.'/') && ! str_starts_with($ruta, '/')
            && ! str_contains($ruta, '\\') && ! in_array('..', explode('/', $ruta), true);
    }
}
