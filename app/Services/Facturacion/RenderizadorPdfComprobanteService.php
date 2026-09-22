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
        $texto = html_entity_decode(strip_tags(str_replace(['</p>', '</div>', '</li>', '<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lineas = array_values(array_filter(array_map('trim', preg_split('/\R/u', $texto)), fn ($v) => $v !== ''));
        $contenido = "BT\n/F1 10 Tf\n50 790 Td\n";
        foreach ($lineas as $linea) {
            foreach ($this->envolver($linea, 92) as $trozo) {
                $contenido .= '('.$this->escapar($trozo).") Tj\n0 -14 Td\n";
            }
        }
        $contenido .= "ET\n".$this->qrVectorial($urlQr, 410, 45, 3);
        $contenido .= "BT /F1 7 Tf 50 28 Td (QR: ".$this->escapar($urlQr).") Tj ET\n";

        return $this->pdf($contenido);
    }

    private function qrVectorial(string $url, int $x, int $y, int $escala): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle(37, 1), new SvgImageBackEnd())))->writeString($url);
        if (! preg_match('/viewBox="0 0 ([0-9.]+) ([0-9.]+)"/', $svg, $vista)) return '';
        preg_match_all('/<path d="([^"]+)"[^>]*fill="(?:#000000|black)"/i', $svg, $paths);
        $salida = "q 0 0 0 rg\n";
        foreach ($paths[1] as $path) {
            preg_match_all('/([MLZ])|(-?\d+(?:\.\d+)?)/', $path, $tokens);
            $valores = $tokens[0]; $comando = null;
            for ($i=0; $i<count($valores); $i++) {
                if (in_array($valores[$i], ['M','L','Z'], true)) { $comando=$valores[$i]; if ($comando==='Z') $salida.="h\n"; continue; }
                if (($comando==='M'||$comando==='L') && isset($valores[$i+1])) {
                    $px=$x+((float)$valores[$i]*$escala); $py=$y+(((float)$vista[2]-(float)$valores[$i+1])*$escala);
                    $salida.=sprintf('%.2F %.2F %s', $px, $py, $comando==='M'?'m':'l')."\n"; $i++; $comando='L';
                }
            }
            $salida .= "f\n";
        }
        return $salida."Q\n";
    }

    private function envolver(string $texto, int $longitud): array
    {
        return explode("\n", wordwrap($texto, $longitud, "\n", true));
    }

    private function escapar(string $texto): string
    {
        $ascii = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $texto);
        return str_replace(['\\','(',')',"\r","\n"], ['\\\\','\\(','\\)','',' '], $ascii === false ? $texto : $ascii);
    }

    private function pdf(string $contenido): string
    {
        $objetos = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            "<< /Length ".strlen($contenido)." >>\nstream\n{$contenido}endstream",
        ];
        $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets=[0];
        foreach ($objetos as $i=>$obj) { $offsets[]=strlen($pdf); $pdf.=($i+1)." 0 obj\n{$obj}\nendobj\n"; }
        $xref=strlen($pdf); $pdf.="xref\n0 ".(count($objetos)+1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets,1) as $offset) $pdf.=sprintf("%010d 00000 n \n",$offset);
        return $pdf."trailer\n<< /Size ".(count($objetos)+1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
