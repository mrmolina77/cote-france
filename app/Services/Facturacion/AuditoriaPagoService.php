<?php

namespace App\Services\Facturacion;

use App\Models\AuditoriaPago;
use App\Models\Pago;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class AuditoriaPagoService
{
    private const CAMPOS_PAGO = ['folio','inscripciones_id','prospectos_id','responsable_pago_id','fecha_pago','moneda',
        'tipo_cambio','monto','metodo_pago_id','forma_pago_sat','banco','referencia','rastreo_spei','ultimos_4_digitos',
        'estado','created_by','confirmed_by','cancelled_by','fecha_confirmacion','fecha_cancelacion','motivo_cancelacion','fecha_reembolso'];
    private const CLAVES_PROHIBIDAS = ['password','password_confirmation','token','secret','cvv','numero_tarjeta','card_number',
        'pdf','bytes','ruta_pdf','signed_url','url_firmada','contenido_correo','body','exception','excepcion','credentials'];

    public function registrar(Pago $pago, string $accion, ?int $usuarioId = null, array $antes = [], array $despues = [], array $metadatos = [], ?string $ip = null, ?string $userAgent = null): AuditoriaPago
    {
        if (! in_array($accion, AuditoriaPago::ACCIONES, true)) throw new InvalidArgumentException('Acción de auditoría no válida.');
        $request = app()->bound('request') ? request() : null;
        $modelo = new AuditoriaPago();
        $modelo->forceFill([
            'pago_id'=>$pago->getKey(), 'accion'=>$accion,
            'usuario_id'=>$usuarioId ?? Auth::id(),
            'ip_address'=>$ip ?? $request?->ip(),
            'user_agent'=>mb_substr((string) ($userAgent ?? $request?->userAgent()), 0, 500) ?: null,
            'ocurrido_en'=>now(), 'valores_anteriores'=>$this->sanitizar($antes),
            'valores_nuevos'=>$this->sanitizar($despues), 'metadatos'=>$this->sanitizar($metadatos),
        ])->save();
        return $modelo;
    }

    public function snapshotPago(Pago $pago): array
    {
        return $this->sanitizar($pago->only(self::CAMPOS_PAGO));
    }

    private function sanitizar(array $datos): array
    {
        $salida = [];
        foreach ($datos as $clave => $valor) {
            $normalizada = strtolower((string) $clave);
            if (collect(self::CLAVES_PROHIBIDAS)->contains(fn ($prohibida) => str_contains($normalizada, $prohibida))) continue;
            if (is_array($valor)) $valor = $this->sanitizar($valor);
            elseif ($valor instanceof \DateTimeInterface) $valor = $valor->format('Y-m-d H:i:s');
            elseif (is_float($valor)) $valor = number_format($valor, 6, '.', '');
            elseif (is_object($valor) || is_resource($valor)) continue;
            $salida[$clave] = $valor;
        }
        ksort($salida);
        return $salida;
    }
}
