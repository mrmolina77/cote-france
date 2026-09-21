<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

trait ArchivoPagoFixtures
{
    /** @var string[] */
    private array $temporalesArchivoPago = [];

    protected function archivoFixture(string $nombre, ?string $nombreCliente = null): UploadedFile
    {
        $contenido = self::bytesFixtureArchivoPago($nombre);
        return $this->archivoDesdeContenido(
            $nombreCliente ?? preg_replace('/\.base64$/', '', $nombre),
            $contenido
        );
    }

    public static function bytesFixtureArchivoPago(string $nombre): string
    {
        // También funciona desde data providers, antes de arrancar Laravel.
        $contenido = file_get_contents(dirname(__DIR__).'/Fixtures/archivos_pago/'.$nombre);
        if (! is_string($contenido)) {
            throw new \RuntimeException('No fue posible leer el fixture '.$nombre.'.');
        }
        if (str_ends_with($nombre, '.base64')) {
            $decodificado = base64_decode(preg_replace('/\s+/', '', $contenido), true);
            if (! is_string($decodificado)) {
                throw new \RuntimeException('El fixture '.$nombre.' no contiene base64 válido.');
            }
            $contenido = $decodificado;
        }

        return $contenido;
    }

    protected function pdfValido(string $nombre = 'comprobante.pdf'): UploadedFile
    {
        return $this->archivoFixture('comprobante.pdf.base64', $nombre);
    }

    protected function archivoDesdeContenido(string $nombre, string $contenido): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'archivo-pago-');
        if ($ruta === false || file_put_contents($ruta, $contenido) !== strlen($contenido)) {
            throw new \RuntimeException('No fue posible crear el fixture temporal.');
        }
        $this->temporalesArchivoPago[] = $ruta;

        return new UploadedFile($ruta, $nombre, null, UPLOAD_ERR_OK, true);
    }

    /** Crea un PDF cuyo último byte pertenece a %%EOF; el relleno vive en un stream. */
    protected function pdfConTamanoExacto(int $tamano): string
    {
        $relleno = max(0, $tamano - 500);
        do {
            $partes = ["%PDF-1.4\n"];
            $offsets = [];
            $objeto = static function (int $numero, string $contenido) use (&$partes, &$offsets): void {
                $offsets[$numero] = strlen(implode('', $partes));
                $partes[] = "{$numero} 0 obj\n{$contenido}\nendobj\n";
            };
            $objeto(1, '<< /Type /Catalog /Pages 2 0 R >>');
            $objeto(2, '<< /Type /Pages /Kids [3 0 R] /Count 1 >>');
            $objeto(3, '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 10 10] >>');
            $objeto(4, "<< /Length {$relleno} >>\nstream\n".str_repeat(' ', $relleno)."\nendstream");
            $xref = strlen(implode('', $partes));
            $partes[] = "xref\n0 5\n0000000000 65535 f \n";
            foreach ($offsets as $offset) {
                $partes[] = sprintf("%010d 00000 n \n", $offset);
            }
            $partes[] = "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
            $pdf = implode('', $partes);
            $diferencia = $tamano - strlen($pdf);
            $relleno += $diferencia;
        } while ($diferencia !== 0 && $relleno >= 0);

        if (strlen($pdf) !== $tamano) {
            throw new \RuntimeException('No fue posible construir el PDF del tamaño solicitado.');
        }

        return $pdf;
    }

    protected function limpiarTemporalesArchivoPago(): void
    {
        foreach ($this->temporalesArchivoPago as $ruta) {
            if (is_file($ruta)) unlink($ruta);
        }
        $this->temporalesArchivoPago = [];
    }
}
