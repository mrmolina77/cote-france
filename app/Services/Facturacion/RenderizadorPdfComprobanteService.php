<?php

namespace App\Services\Facturacion;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** Renderizador PDF autocontenido; el QR usa la dependencia directa bacon/bacon-qr-code. */
class RenderizadorPdfComprobanteService
{
    public function renderizar(string $html, string $urlQr): string
    {
        // Generar QR en base64 (PNG es más confiable en dompdf)
        $qrImage = (new Writer(new ImageRenderer(new RendererStyle(150, 1), new \BaconQrCode\Renderer\Image\SvgImageBackEnd())))->writeString($urlQr);
        $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrImage);

        // Pass to DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('letter');
        
        return $pdf->output();
    }
}
