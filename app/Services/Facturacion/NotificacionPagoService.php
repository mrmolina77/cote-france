<?php

namespace App\Services\Facturacion;

use App\Jobs\EnviarNotificacionPago;
use App\Models\ComprobantePago;
use App\Models\NotificacionPago;
use App\Models\Pago;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class NotificacionPagoService
{
    public function solicitarInicialRecibido(Pago $pago, ComprobantePago $comprobante): NotificacionPago
    {
        return $this->crear($pago, $comprobante, NotificacionPago::TIPO_RECIBIDO,
            NotificacionPago::SOLICITUD_INICIAL, 'recibido:'.$pago->getKey(), null);
    }

    public function solicitarInicialCancelado(Pago $pago): NotificacionPago
    {
        $version = optional($pago->fecha_cancelacion)->format('YmdHis') ?: 'cancelado';
        return $this->crear($pago, $pago->comprobantePago, NotificacionPago::TIPO_CANCELADO,
            NotificacionPago::SOLICITUD_INICIAL, 'cancelado:'.$pago->getKey().':'.$version, null);
    }

    public function solicitarReenvio(Pago $pago, ComprobantePago $comprobante, int $usuarioId, string $token): NotificacionPago
    {
        if ($pago->estado !== Pago::ESTADO_CONFIRMADO || (int) $comprobante->pago_id !== (int) $pago->getKey()) {
            throw ValidationException::withMessages(['recibo' => 'El recibo seleccionado no está disponible para envío.']);
        }
        if (! $this->comprobanteLegible($comprobante)) {
            throw ValidationException::withMessages(['recibo' => 'El archivo privado del recibo no está disponible o no es íntegro.']);
        }

        return $this->crear($pago, $comprobante, NotificacionPago::TIPO_RECIBIDO,
            NotificacionPago::SOLICITUD_REENVIO, 'reenvio:'.hash('sha256', $token), $usuarioId);
    }

    private function crear(Pago $pago, ?ComprobantePago $comprobante, string $tipo, string $solicitud, string $clave, ?int $usuarioId): NotificacionPago
    {
        return DB::transaction(function () use ($pago, $comprobante, $tipo, $solicitud, $clave, $usuarioId) {
            $pago = Pago::query()->with(['responsablePago', 'comprobantePago'])->whereKey($pago->getKey())->lockForUpdate()->firstOrFail();
            if ($tipo === NotificacionPago::TIPO_RECIBIDO && ($pago->estado !== Pago::ESTADO_CONFIRMADO || ! $comprobante
                || (int) $comprobante->pago_id !== (int) $pago->getKey())) {
                throw ValidationException::withMessages(['recibo' => 'El pago confirmado no tiene un recibo válido para enviar.']);
            }
            if ($tipo === NotificacionPago::TIPO_CANCELADO && $pago->estado !== Pago::ESTADO_CANCELADO) {
                throw ValidationException::withMessages(['pago' => 'El pago no está cancelado.']);
            }

            $correo = mb_strtolower(trim((string) optional($pago->responsablePago)->correo));
            $valido = filter_var($correo, FILTER_VALIDATE_EMAIL) !== false && strlen($correo) <= 254;
            $datos = [
                'pago_id' => $pago->getKey(), 'comprobante_pago_id' => $comprobante?->getKey(),
                'tipo' => $tipo, 'tipo_solicitud' => $solicitud, 'clave_idempotencia' => $clave,
                'destinatario' => $valido ? $correo : 'sin-destinatario@example.invalid',
                'estado' => $valido ? NotificacionPago::ESTADO_PENDIENTE : NotificacionPago::ESTADO_OMITIDO,
                'intentos' => 0, 'programado_en' => now(), 'solicitado_por' => $usuarioId,
                'solicitado_en' => $usuarioId ? now() : null,
                'ultimo_error' => $valido ? null : 'El responsable de pago no tiene un correo válido.',
            ];
            try {
                $notificacion = new NotificacionPago();
                $notificacion->forceFill($datos)->save();
            } catch (QueryException $e) {
                if ($this->esClaveDuplicada($e)) {
                    return NotificacionPago::query()->where('clave_idempotencia', $clave)->firstOrFail();
                }
                throw $e;
            }
            if ($valido) EnviarNotificacionPago::dispatch($notificacion->getKey())->afterCommit();
            return $notificacion;
        }, 3);
    }

    public function comprobanteLegible(ComprobantePago $comprobante): bool
    {
        if ($comprobante->disco !== ComprobantePago::DISCO_PRIVADO || $comprobante->mime_type !== ComprobantePago::MIME_PDF
            || ! app(GeneradorComprobantePagoService::class)->rutaPermitida((string) $comprobante->ruta_pdf)) return false;
        $disco = Storage::disk(ComprobantePago::DISCO_PRIVADO);
        if (! $disco->exists($comprobante->ruta_pdf)) return false;
        $bytes = $disco->get($comprobante->ruta_pdf);
        return hash_equals((string) $comprobante->hash_sha256, hash('sha256', $bytes));
    }

    private function esClaveDuplicada(QueryException $e): bool
    {
        return in_array((string) ($e->errorInfo[0] ?? ''), ['23000', '23505'], true);
    }
}
