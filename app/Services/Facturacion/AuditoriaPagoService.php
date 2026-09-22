<?php

namespace App\Services\Facturacion;

use App\Models\AuditoriaPago;
use App\Models\Pago;
use DateTimeInterface;
use DateTimeImmutable;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class AuditoriaPagoService
{
    private const MAX_DEPTH = 4;
    private const MAX_STRING = 500;
    private const MAX_JSON_BYTES = 32768;

    /** Campos financieros que pueden formar parte de un snapshot de Pago. */
    private const CAMPOS_PAGO = [
        'folio', 'inscripciones_id', 'prospectos_id', 'responsable_pago_id', 'fecha_pago', 'zona_horaria',
        'moneda', 'tipo_cambio', 'monto', 'metodo_pago_id', 'forma_pago_sat', 'banco', 'referencia',
        'numero_cheque', 'rastreo_spei', 'numero_autorizacion', 'terminal', 'ultimos_4_digitos', 'proveedor',
        'anticipo_relacionado_id', 'identificador_transaccion_externa', 'fecha_movimiento', 'estado',
        'created_by', 'confirmed_by', 'cancelled_by', 'fecha_confirmacion', 'fecha_cancelacion',
        'motivo_cancelacion', 'fecha_reembolso',
    ];

    private const CAMPOS_RECIBO = ['comprobante_pago_id', 'folio', 'hash_sha256', 'tamano_bytes', 'generado_en', 'estado'];
    private const CAMPOS_CORREO = ['estado'];

    private const META_POR_ACCION = [
        AuditoriaPago::CREAR => ['operacion_atomica', 'aplicaciones', 'cargos'],
        AuditoriaPago::MODIFICAR => ['campos_modificados'],
        AuditoriaPago::CONFIRMAR => ['operacion_atomica', 'aplicaciones', 'cargos'],
        AuditoriaPago::CANCELAR => ['motivo', 'cargos'],
        AuditoriaPago::REEMBOLSAR => ['motivo', 'cargos'],
        AuditoriaPago::GENERAR_RECIBO => ['comprobante_pago_id'],
        AuditoriaPago::REGENERAR_RECIBO => ['comprobante_pago_id'],
        AuditoriaPago::ENVIAR_CORREO => ['notificacion_pago_id', 'tipo', 'tipo_solicitud', 'destinatario', 'estado'],
        AuditoriaPago::REENVIAR_CORREO => ['notificacion_pago_id', 'tipo', 'tipo_solicitud', 'destinatario', 'estado'],
        AuditoriaPago::CORREO_ENVIADO => ['notificacion_pago_id', 'intento', 'transicion'],
        AuditoriaPago::CORREO_FALLIDO => ['notificacion_pago_id', 'intento', 'transicion'],
        AuditoriaPago::CORREO_OMITIDO => ['notificacion_pago_id', 'intento', 'transicion'],
    ];

    private const CAMPOS_APLICACION = ['cargo_id', 'importe_aplicado', 'saldo_anterior', 'saldo_posterior'];
    private const CAMPOS_CARGO = ['cargo_id', 'saldo_anterior', 'saldo_posterior', 'estado_anterior', 'estado_nuevo'];
    private const IMPORTES = ['monto', 'tipo_cambio', 'importe_aplicado', 'saldo_anterior', 'saldo_posterior'];
    private const FECHAS = ['fecha_pago', 'fecha_movimiento', 'fecha_confirmacion', 'fecha_cancelacion', 'fecha_reembolso', 'generado_en'];

    public function registrar(Pago $pago, string $accion, ?int $usuarioId = null, array $antes = [], array $despues = [], array $metadatos = [], ?string $ip = null, ?string $userAgent = null): AuditoriaPago
    {
        if (! in_array($accion, AuditoriaPago::ACCIONES, true)) {
            throw new InvalidArgumentException('Acción de auditoría no válida.');
        }

        $request = app()->bound('request') ? request() : null;
        $modelo = new AuditoriaPago();
        $modelo->forceFill([
            'pago_id' => $pago->getKey(),
            'accion' => $accion,
            'usuario_id' => $usuarioId ?? Auth::id(),
            'ip_address' => $this->normalizarIp($ip ?? $request?->ip()),
            'user_agent' => $this->normalizarTexto($userAgent ?? $request?->userAgent(), 500),
            'ocurrido_en' => now(),
            'valores_anteriores' => $this->snapshotPorAccion($accion, $antes),
            'valores_nuevos' => $this->snapshotPorAccion($accion, $despues),
            'metadatos' => $this->metadatosPorAccion($accion, $metadatos),
        ])->save();

        return $modelo;
    }

    public function snapshotPago(Pago $pago): array
    {
        return $this->filtrar($pago->only(self::CAMPOS_PAGO), self::CAMPOS_PAGO);
    }

    /**
     * No existe actualmente una operación funcional para editar pagos. Este contrato
     * permite auditarla sin reutilizar acciones específicas cuando se incorpore una.
     */
    public function registrarModificacion(Pago $pago, array $antes, array $despues, ?int $usuarioId = null): AuditoriaPago
    {
        $antes = $this->filtrar($antes, self::CAMPOS_PAGO);
        $despues = $this->filtrar($despues, self::CAMPOS_PAGO);
        $campos = array_values(array_filter(array_keys($despues), fn ($campo) => ($antes[$campo] ?? null) !== $despues[$campo]));

        return $this->registrar($pago, AuditoriaPago::MODIFICAR, $usuarioId,
            array_intersect_key($antes, array_flip($campos)), array_intersect_key($despues, array_flip($campos)),
            ['campos_modificados' => $campos]);
    }

    private function snapshotPorAccion(string $accion, array $datos): array
    {
        $campos = in_array($accion, [AuditoriaPago::GENERAR_RECIBO, AuditoriaPago::REGENERAR_RECIBO], true)
            ? self::CAMPOS_RECIBO
            : (in_array($accion, [AuditoriaPago::ENVIAR_CORREO, AuditoriaPago::REENVIAR_CORREO,
                AuditoriaPago::CORREO_ENVIADO, AuditoriaPago::CORREO_FALLIDO, AuditoriaPago::CORREO_OMITIDO], true)
                ? self::CAMPOS_CORREO : self::CAMPOS_PAGO);

        return $this->limitarJson($this->filtrar($datos, $campos));
    }

    private function metadatosPorAccion(string $accion, array $datos): array
    {
        $salida = $this->filtrar($datos, self::META_POR_ACCION[$accion] ?? []);
        foreach (['aplicaciones' => self::CAMPOS_APLICACION, 'cargos' => self::CAMPOS_CARGO] as $clave => $campos) {
            if (! isset($salida[$clave]) || ! is_array($salida[$clave])) continue;
            $salida[$clave] = array_values(array_map(
                fn ($fila) => is_array($fila) ? $this->filtrar($fila, $campos, 2) : [],
                $salida[$clave]
            ));
        }
        if (isset($salida['campos_modificados']) && is_array($salida['campos_modificados'])) {
            $salida['campos_modificados'] = array_values(array_intersect($salida['campos_modificados'], self::CAMPOS_PAGO));
        }
        if (isset($salida['destinatario'])) {
            $correo = mb_strtolower(trim((string) $salida['destinatario']));
            $salida['destinatario'] = filter_var($correo, FILTER_VALIDATE_EMAIL) && strlen($correo) <= 254 ? $correo : 'sin-destinatario@example.invalid';
        }

        return $this->limitarJson($salida);
    }

    private function filtrar(array $datos, array $permitidos, int $profundidad = 0): array
    {
        if ($profundidad >= self::MAX_DEPTH) return [];
        $salida = [];
        foreach ($permitidos as $clave) {
            if (! array_key_exists($clave, $datos)) continue;
            $valor = $datos[$clave];
            if ($valor instanceof DateTimeInterface) $valor = $valor->format('Y-m-d H:i:s');
            elseif (in_array($clave, self::FECHAS, true) && is_string($valor)) {
                $valor = $this->fecha($valor, $clave);
            } elseif (in_array($clave, self::IMPORTES, true) && is_numeric($valor)) {
                $escala = $clave === 'tipo_cambio' ? 6 : 2;
                $valor = $this->decimal((string) $valor, $escala);
            } elseif (is_string($valor)) $valor = $this->normalizarTexto($valor);
            elseif (is_array($valor)) {
                if (! in_array($clave, ['aplicaciones', 'cargos', 'campos_modificados'], true)) continue;
            } elseif (is_object($valor) || is_resource($valor)) continue;
            $salida[$clave] = $valor;
        }
        ksort($salida);
        return $salida;
    }

    private function limitarJson(array $datos): array
    {
        try {
            $json = json_encode($datos, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException('El payload de auditoría no contiene JSON válido.', 0, $e);
        }

        // Un evento financiero nunca se trunca: es preferible abortar la misma
        // transacción a conservar un snapshot parcial que parezca completo.
        if (strlen($json) > self::MAX_JSON_BYTES) {
            throw new InvalidArgumentException('El payload de auditoría excede el límite de 32768 bytes.');
        }

        return $datos;
    }

    private function fecha(string $valor, string $campo): string
    {
        $valor = trim($valor);
        foreach (['!Y-m-d H:i:s', '!Y-m-d\TH:i:sP', '!Y-m-d'] as $formato) {
            $fecha = DateTimeImmutable::createFromFormat($formato, $valor);
            $errores = DateTimeImmutable::getLastErrors();
            if ($fecha !== false && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))) {
                return $fecha->format('Y-m-d H:i:s');
            }
        }

        throw new InvalidArgumentException("La fecha {$campo} no es válida.");
    }

    private function normalizarTexto($valor, int $limite = self::MAX_STRING): ?string
    {
        if ($valor === null) return null;
        $texto = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $valor) ?? '');
        return ($texto = mb_substr($texto, 0, $limite)) !== '' ? $texto : null;
    }

    private function normalizarIp($ip): ?string
    {
        $ip = is_string($ip) ? trim($ip) : '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    private function decimal(string $valor, int $escala): string
    {
        if (preg_match('/^(-?\d+)(?:\.(\d+))?$/D', $valor, $partes) !== 1) return str_repeat('0', 1).'.'.str_repeat('0', $escala);
        return $partes[1].'.'.str_pad(substr($partes[2] ?? '', 0, $escala), $escala, '0');
    }
}
