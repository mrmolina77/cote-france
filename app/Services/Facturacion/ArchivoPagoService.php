<?php

namespace App\Services\Facturacion;

use App\Models\ArchivoPago;
use App\Models\Pago;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ArchivoPagoService
{
    public const DISCO = 'local';
    public const DIRECTORIO = 'archivos_pago';
    public const MAXIMO_BYTES = 10240 * 1024;

    private const EXTENSIONES = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
    ];

    /** @return array{mime_type:string, extension:string, tamano_bytes:int, nombre_original:string} */
    public function validar(UploadedFile $archivo): array
    {
        if (! $archivo->isValid() || ! is_file($archivo->getRealPath())) {
            $this->invalido('El comprobante no pudo cargarse o ya no está disponible.');
        }
        $tamano = $archivo->getSize();
        if (! is_int($tamano) || $tamano < 1) $this->invalido('El comprobante no puede estar vacío.');
        if ($tamano > self::MAXIMO_BYTES) $this->invalido('El comprobante no debe superar 10240 KB.');

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($archivo->getRealPath());
        if (! is_string($mime) || ! isset(self::EXTENSIONES[$mime])) {
            $this->invalido('El contenido del comprobante debe ser PDF, JPEG o PNG válido.');
        }
        $extensionCliente = strtolower((string) pathinfo($archivo->getClientOriginalName(), PATHINFO_EXTENSION));
        if (! in_array($extensionCliente, self::EXTENSIONES[$mime], true)) {
            $this->invalido('La extensión del comprobante no corresponde con su contenido.');
        }

        return [
            'mime_type' => $mime,
            'extension' => self::EXTENSIONES[$mime][0],
            'tamano_bytes' => $tamano,
            'nombre_original' => $this->sanearNombre($archivo->getClientOriginalName(), self::EXTENSIONES[$mime][0]),
        ];
    }

    public function guardar(Pago $pago, UploadedFile $archivo, int $usuarioId, ?callable $alAsignarRuta = null): ArchivoPago
    {
        $datos = $this->validar($archivo);
        $ruta = self::DIRECTORIO.'/'.date('Y/m').'/'.bin2hex(random_bytes(20)).'.'.$datos['extension'];
        if ($alAsignarRuta !== null) {
            $alAsignarRuta($ruta);
        }

        $stream = null;
        try {
            $stream = fopen($archivo->getRealPath(), 'rb');
            if ($stream === false) throw new RuntimeException('No fue posible leer el comprobante temporal.');
            $guardado = Storage::disk(self::DISCO)->put($ruta, $stream, ['visibility' => 'private']);
            if ($guardado !== true || ! Storage::disk(self::DISCO)->exists($ruta)) {
                throw new RuntimeException('No fue posible guardar el comprobante privado.');
            }

            $modelo = new ArchivoPago();
            $modelo->forceFill([
                'pago_id' => $pago->getKey(), 'nombre_original' => $datos['nombre_original'],
                'disco' => self::DISCO, 'ruta' => $ruta, 'mime_type' => $datos['mime_type'],
                'tamano_bytes' => $datos['tamano_bytes'], 'created_by' => $usuarioId,
            ])->save();
            return $modelo;
        } catch (\Throwable $error) {
            // Cuando existe coordinador, éste compensa también los errores posteriores
            // de la transacción. Sin coordinador, guardar() sigue siendo seguro por sí solo.
            if ($alAsignarRuta === null) $this->eliminarRutaNueva($ruta, $error);
            throw $error;
        } finally {
            if (is_resource($stream)) fclose($stream);
        }
    }

    public function eliminarNuevo(ArchivoPago $archivo, \Throwable $errorPrincipal): void
    {
        if ($archivo->disco === self::DISCO && $this->rutaPermitida($archivo->ruta)) {
            $this->eliminarRutaNueva($archivo->ruta, $errorPrincipal);
        }
    }

    public function eliminarRutaNueva(string $ruta, \Throwable $errorPrincipal): void
    {
        if ($this->rutaPermitida($ruta)) $this->eliminarCompensacion($ruta, $errorPrincipal);
    }

    public function rutaPermitida(string $ruta): bool
    {
        return str_starts_with($ruta, self::DIRECTORIO.'/')
            && ! str_starts_with($ruta, '/') && ! str_contains($ruta, '\\')
            && ! in_array('..', explode('/', $ruta), true);
    }

    private function sanearNombre(string $nombre, string $extension): string
    {
        // Un delimitador alternativo evita que '/' pueda cerrar accidentalmente
        // el patrón PHP. Los separadores y todos los controles quedan neutralizados.
        $nombre = preg_replace('~[\x00-\x1F\x7F/\\\\]+~u', '_', $nombre) ?? '';
        $nombre = preg_replace('~\.{2,}~u', '_', $nombre) ?? '';
        $nombre = trim($nombre, " .\t\n\r\0\x0B");
        if ($nombre === '') $nombre = 'comprobante.'.$extension;

        $sufijo = '.'.strtolower((string) pathinfo($nombre, PATHINFO_EXTENSION));
        $extensionesNombre = $extension === 'jpg' ? ['jpg', 'jpeg'] : [$extension];
        if ($sufijo === '.' || ! in_array(substr($sufijo, 1), $extensionesNombre, true)) {
            $sufijo = '.'.$extension;
        }
        $base = pathinfo($nombre, PATHINFO_FILENAME);
        $maximoBase = 240 - mb_strlen($sufijo, 'UTF-8');

        return mb_substr($base, 0, max(1, $maximoBase), 'UTF-8').$sufijo;
    }

    private function eliminarCompensacion(string $ruta, \Throwable $errorPrincipal): void
    {
        try {
            if (! Storage::disk(self::DISCO)->delete($ruta) && Storage::disk(self::DISCO)->exists($ruta)) {
                throw new RuntimeException('El filesystem rechazó la eliminación compensatoria.');
            }
        } catch (\Throwable $compensacion) {
            report(new RuntimeException('Falló la compensación del comprobante '.$ruta, 0, $compensacion));
        }
    }

    private function invalido(string $mensaje): void
    {
        throw ValidationException::withMessages(['comprobante' => $mensaje]);
    }
}
