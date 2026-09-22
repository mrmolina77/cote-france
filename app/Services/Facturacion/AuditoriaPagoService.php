<?php

namespace App\Services\Facturacion;

use App\Models\AuditoriaPago;
use App\Models\Pago;
use DateTimeInterface;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Único punto de escritura de la bitácora de pagos.
 *
 * No existe actualmente una operación funcional para editar pagos. MODIFICAR se
 * conserva como contrato para que una futura edición autorizada registre aquí,
 * dentro de su misma transacción, los snapshots anterior y posterior.
 */
class AuditoriaPagoService
{
    private const MAX_JSON_BYTES = 32768;
    private const MAX_DEPTH = 4;
    private const MAX_ITEMS = 100;

    private const CAMPOS_PAGO = [
        'folio', 'inscripciones_id', 'prospectos_id', 'responsable_pago_id', 'fecha_pago', 'zona_horaria',
        'moneda', 'tipo_cambio', 'monto', 'metodo_pago_id', 'forma_pago_sat', 'banco', 'referencia',
        'numero_cheque', 'rastreo_spei', 'numero_autorizacion', 'terminal', 'ultimos_4_digitos', 'proveedor',
        'anticipo_relacionado_id', 'identificador_transaccion_externa', 'fecha_movimiento',
        'estado', 'created_by', 'confirmed_by',
        'cancelled_by', 'fecha_confirmacion', 'fecha_cancelacion', 'motivo_cancelacion', 'fecha_reembolso',
    ];

    private const ESQUEMA_PAGO = [
        'folio'=>true, 'inscripciones_id'=>true, 'prospectos_id'=>true, 'responsable_pago_id'=>true,
        'fecha_pago'=>true, 'zona_horaria'=>true, 'moneda'=>true, 'tipo_cambio'=>true, 'monto'=>true,
        'metodo_pago_id'=>true, 'forma_pago_sat'=>true, 'banco'=>true, 'referencia'=>true,
        'numero_cheque'=>true, 'rastreo_spei'=>true, 'numero_autorizacion'=>true, 'terminal'=>true,
        'ultimos_4_digitos'=>true, 'proveedor'=>true, 'anticipo_relacionado_id'=>true,
        'identificador_transaccion_externa'=>true, 'fecha_movimiento'=>true, 'estado'=>true,
        'created_by'=>true, 'confirmed_by'=>true, 'cancelled_by'=>true, 'fecha_confirmacion'=>true,
        'fecha_cancelacion'=>true, 'motivo_cancelacion'=>true, 'fecha_reembolso'=>true,
    ];

    private const ESQUEMA_RECIBO = [
        'comprobante_pago_id'=>true, 'folio'=>true, 'hash_sha256'=>true, 'tamano_bytes'=>true,
        'generado_en'=>true, 'estado'=>true,
    ];

    private const ESQUEMA_CORREO = [
        'estado'=>true, 'programado_en'=>true, 'solicitado_en'=>true, 'enviado_en'=>true,
    ];

    private const META_APLICACION = [
        'cargo_id'=>true, 'importe_aplicado'=>true, 'saldo_anterior'=>true, 'saldo_posterior'=>true,
        'estado_anterior'=>true, 'estado_nuevo'=>true,
    ];

    private const META_CARGO = [
        'cargo_id'=>true, 'saldo_anterior'=>true, 'saldo_posterior'=>true, 'saldo_restaurado'=>true,
        'total'=>true, 'estado_anterior'=>true, 'estado_nuevo'=>true,
    ];

    public function registrar(Pago $pago, string $accion, ?int $usuarioId = null, array $antes = [], array $despues = [], array $metadatos = [], ?string $ip = null, ?string $userAgent = null): AuditoriaPago
    {
        if (! in_array($accion, AuditoriaPago::ACCIONES, true)) {
            throw new InvalidArgumentException('Acción de auditoría no válida.');
        }

        $request = app()->bound('request') ? request() : null;
        $valoresAnteriores = $this->filtrar($antes, $this->esquemaSnapshot($accion));
        $valoresNuevos = $this->filtrar($despues, $this->esquemaSnapshot($accion));
        $metadatosFiltrados = $this->filtrar($metadatos, $this->esquemaMetadatos($accion));
        if ($this->tamanoJson($valoresAnteriores) + $this->tamanoJson($valoresNuevos)
            + $this->tamanoJson($metadatosFiltrados) > self::MAX_JSON_BYTES) {
            throw new InvalidArgumentException('Los datos de auditoría exceden el tamaño permitido.');
        }

        $modelo = new AuditoriaPago();
        $modelo->forceFill([
            'pago_id' => $pago->getKey(),
            'accion' => $accion,
            'usuario_id' => $usuarioId ?? Auth::id(),
            'ip_address' => $this->normalizarIp($ip ?? $request?->ip()),
            'user_agent' => $this->texto($userAgent ?? $request?->userAgent(), 500),
            'ocurrido_en' => now(),
            'valores_anteriores' => $valoresAnteriores,
            'valores_nuevos' => $valoresNuevos,
            'metadatos' => $metadatosFiltrados,
        ])->save();

        return $modelo;
    }

    public function snapshotPago(Pago $pago): array
    {
        return $this->filtrar($pago->only(self::CAMPOS_PAGO), self::ESQUEMA_PAGO);
    }

    private function esquemaSnapshot(string $accion): array
    {
        if ($accion === AuditoriaPago::CONFIRMAR) {
            // Describe la operación lógica; nunca pretende representar una fila borrador.
            return self::ESQUEMA_PAGO + ['estado_previo'=>true];
        }
        if (in_array($accion, [AuditoriaPago::GENERAR_RECIBO, AuditoriaPago::REGENERAR_RECIBO], true)) {
            return self::ESQUEMA_RECIBO;
        }
        if (in_array($accion, [AuditoriaPago::ENVIAR_CORREO, AuditoriaPago::REENVIAR_CORREO,
            AuditoriaPago::CORREO_ENVIADO, AuditoriaPago::CORREO_FALLIDO, AuditoriaPago::CORREO_OMITIDO], true)) {
            return self::ESQUEMA_CORREO;
        }

        return self::ESQUEMA_PAGO;
    }

    private function esquemaMetadatos(string $accion): array
    {
        if (in_array($accion, [AuditoriaPago::CREAR, AuditoriaPago::CONFIRMAR], true)) {
            return ['operacion_atomica'=>true, 'aplicaciones'=>['*'=>self::META_APLICACION], 'cargos'=>['*'=>self::META_CARGO]];
        }
        if ($accion === AuditoriaPago::CANCELAR) {
            return ['motivo'=>true, 'cargos'=>['*'=>self::META_CARGO]];
        }
        if (in_array($accion, [AuditoriaPago::GENERAR_RECIBO, AuditoriaPago::REGENERAR_RECIBO], true)) {
            return self::ESQUEMA_RECIBO;
        }
        if (in_array($accion, [AuditoriaPago::ENVIAR_CORREO, AuditoriaPago::REENVIAR_CORREO], true)) {
            return ['notificacion_pago_id'=>true, 'tipo'=>true, 'tipo_solicitud'=>true, 'destinatario'=>true, 'estado'=>true];
        }
        if (in_array($accion, [AuditoriaPago::CORREO_ENVIADO, AuditoriaPago::CORREO_FALLIDO, AuditoriaPago::CORREO_OMITIDO], true)) {
            return ['notificacion_pago_id'=>true, 'intento'=>true, 'transicion'=>true];
        }
        if ($accion === AuditoriaPago::MODIFICAR) {
            return ['campos_modificados'=>['*'=>'campo_pago']];
        }

        return [];
    }

    private function filtrar(array $datos, array $esquema, int $profundidad = 0): array
    {
        if ($profundidad >= self::MAX_DEPTH) {
            return [];
        }
        $salida = [];
        foreach (array_slice($datos, 0, self::MAX_ITEMS, true) as $clave => $valor) {
            $regla = $esquema[$clave] ?? ($esquema['*'] ?? null);
            if ($regla === null) {
                continue;
            }
            if ($regla === 'campo_pago') {
                if (! is_string($valor) || ! in_array($valor, self::CAMPOS_PAGO, true)) {
                    continue;
                }
                $salida[$clave] = $valor;
                continue;
            }
            if (is_array($valor)) {
                if (! is_array($regla)) {
                    continue;
                }
                $valor = $this->filtrar($valor, $regla, $profundidad + 1);
            } elseif (is_object($valor) && ! $valor instanceof DateTimeInterface || is_resource($valor)) {
                continue;
            } else {
                $valor = $this->normalizarValor((string) $clave, $valor);
            }
            $salida[$clave] = $valor;
        }
        ksort($salida);
        if ($this->tamanoJson($salida) > self::MAX_JSON_BYTES) {
            throw new InvalidArgumentException('Los datos de auditoría exceden el tamaño permitido.');
        }

        return $salida;
    }

    private function tamanoJson(array $datos): int
    {
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new InvalidArgumentException('Los datos de auditoría no son serializables.');
        }

        return strlen($json);
    }

    private function normalizarValor(string $clave, $valor)
    {
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d\TH:i:sP');
        }
        if ($valor === null || is_bool($valor) || is_int($valor)) {
            return $valor;
        }
        if (in_array($clave, ['monto', 'tipo_cambio', 'importe_aplicado', 'saldo_anterior', 'saldo_posterior', 'saldo_restaurado', 'total'], true)) {
            $texto = (string) $valor;
            return preg_match('/^-?\d+(?:\.\d{1,6})?$/D', $texto) === 1 ? $texto : null;
        }
        if (str_contains($clave, 'fecha') || str_ends_with($clave, '_en')) {
            try {
                return \Carbon\CarbonImmutable::parse((string) $valor)->format('Y-m-d\TH:i:sP');
            } catch (\Throwable $e) {
                return null;
            }
        }
        if ($clave === 'destinatario') {
            $correo = mb_strtolower(trim((string) $valor));
            return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false ? mb_substr($correo, 0, 254) : 'sin-destinatario@example.invalid';
        }

        return $this->texto($valor, $clave === 'motivo' || $clave === 'motivo_cancelacion' ? 2000 : 500);
    }

    private function normalizarIp(?string $ip): ?string
    {
        $ip = trim((string) $ip);
        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? mb_substr($ip, 0, 45) : null;
    }

    private function texto($valor, int $limite): ?string
    {
        if ($valor === null || is_array($valor) || is_object($valor) || is_resource($valor)) {
            return null;
        }
        $texto = trim((string) $valor);
        return $texto === '' ? null : mb_substr($texto, 0, $limite);
    }
}
