<?php

namespace App\Services\Facturacion;

use App\Models\Cargo;
use App\Models\Inscripcion;
use App\Models\Pago;
use App\Models\PagoAplicacion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;

class AplicarPagoService
{
    public function __construct(
        private MetodoPagoBehaviorService $metodos,
        private GeneradorFolioPagoService $folios,
        private ArchivoPagoService $archivos
    ) {
    }

    /**
     * Confirma un pago ordinario. Los pagos sin aplicaciones se habilitarán
     * exclusivamente en el bloque de anticipos.
     */
    public function confirmar(
        int $inscripcionId,
        int $metodoPagoId,
        array $datosPago,
        array $importesPorCargo,
        int $usuarioId
    ): Pago {
        $rutaArchivoNuevo = null;
        try {
            return DB::transaction(function () use ($inscripcionId, $metodoPagoId, $datosPago, $importesPorCargo, $usuarioId, &$rutaArchivoNuevo) {
            $inscripcion = Inscripcion::query()->with(['prospecto', 'responsablePago'])->find($inscripcionId);
            if (! $inscripcion || ! $inscripcion->prospecto || $inscripcion->estatus === 'cancelada') {
                throw ValidationException::withMessages(['inscripcion_id' => 'La inscripción seleccionada no está disponible.']);
            }
            if (! $inscripcion->responsablePago || ! $inscripcion->responsablePago->activo
                || ($inscripcion->responsablePago->prospectos_id !== null
                    && (int) $inscripcion->responsablePago->prospectos_id !== (int) $inscripcion->prospectos_id)) {
                throw ValidationException::withMessages(['inscripcion_id' => 'La inscripción no tiene un responsable de pago activo.']);
            }

            $resultadoMetodo = $this->metodos->validarYNormalizarParaNuevoPago($metodoPagoId, $datosPago);
            $metodo = $resultadoMetodo['metodo'];
            if ($metodo->requiere_anticipo_relacionado) {
                throw ValidationException::withMessages(['metodo_pago_id' => 'La aplicación de anticipos se habilitará en el bloque correspondiente.']);
            }
            $comprobante = $resultadoMetodo['datos']['comprobante'] ?? null;
            if ($comprobante instanceof UploadedFile) {
                // Se repite al guardar para detectar temporales vencidos o modificados.
                $this->archivos->validar($comprobante);
            }

            [$ids, $importes] = $this->normalizarAplicaciones($importesPorCargo);
            if ($ids === []) {
                throw ValidationException::withMessages(['aplicaciones' => 'Un pago ordinario debe aplicarse al menos a un cargo.']);
            }

            $cargos = Cargo::query()->whereIn('cargo_id', $ids)->orderBy('cargo_id')->lockForUpdate()->get()->keyBy('cargo_id');
            if ($cargos->count() !== count($ids)) {
                throw ValidationException::withMessages(['aplicaciones' => 'Uno o más cargos seleccionados ya no están disponibles.']);
            }

            $totalAplicado = 0;
            $saldos = [];
            foreach ($ids as $cargoId) {
                /** @var Cargo $cargo */
                $cargo = $cargos->get($cargoId);
                if ((int) $cargo->inscripciones_id !== (int) $inscripcion->getKey()) {
                    throw ValidationException::withMessages(['aplicaciones' => 'Uno o más cargos seleccionados ya no están disponibles.']);
                }
                if ($cargo->moneda !== $inscripcion->moneda) {
                    throw ValidationException::withMessages(['aplicaciones' => 'La moneda de los cargos debe coincidir con la inscripción.']);
                }
                if (! in_array($cargo->estado, [Cargo::ESTADO_PENDIENTE, Cargo::ESTADO_PARCIAL, Cargo::ESTADO_VENCIDO], true)) {
                    throw ValidationException::withMessages(['aplicaciones' => 'Uno o más cargos ya no admiten pagos.']);
                }
                $saldo = $this->aCentavos($cargo->saldo_pendiente, 'saldo pendiente');
                if ($saldo <= 0 || $importes[$cargoId] > $saldo) {
                    throw ValidationException::withMessages(['aplicaciones' => 'El importe no puede superar el saldo pendiente actual.']);
                }
                $saldos[$cargoId] = [$saldo, $saldo - $importes[$cargoId]];
                $totalAplicado += $importes[$cargoId];
            }

            $monto = $this->aCentavos($datosPago['monto'] ?? null, 'monto');
            if ($monto <= 0) {
                throw ValidationException::withMessages(['monto' => 'El monto recibido debe ser mayor a 0.00.']);
            }
            if ($totalAplicado > $monto) {
                throw ValidationException::withMessages(['aplicaciones' => 'El total aplicado no puede superar el monto recibido.']);
            }
            if ($totalAplicado !== $monto) {
                throw ValidationException::withMessages(['aplicaciones' => 'El total aplicado debe coincidir exactamente con el monto recibido.']);
            }

            $base = Validator::make($datosPago, [
                'fecha_pago' => ['required', 'date'],
                'zona_horaria' => ['required', 'string', 'timezone', 'max:64'],
                'tipo_cambio' => ['nullable', 'regex:/^(?:0|[1-9]\d{0,11})(?:\.\d{1,6})?$/D'],
                'observaciones' => ['nullable', 'string', 'max:2000'],
                'fecha_movimiento' => ['nullable', 'date'],
                'identificador_transaccion_externa' => ['nullable', 'string', 'max:255'],
            ])->validate();
            $fechaPago = CarbonImmutable::parse($base['fecha_pago'], $base['zona_horaria']);
            $folio = $this->folios->generar($fechaPago, $base['zona_horaria']);

            $dinamicos = $resultadoMetodo['datos'];
            unset($dinamicos['comprobante'], $dinamicos['anticipo_relacionado_id']);
            $pago = new Pago();
            $pago->forceFill(array_merge($dinamicos, [
                'folio' => $folio,
                'inscripciones_id' => $inscripcion->getKey(),
                'prospectos_id' => $inscripcion->prospectos_id,
                'responsable_pago_id' => $inscripcion->responsable_pago_id,
                'fecha_pago' => $fechaPago,
                'zona_horaria' => $base['zona_horaria'],
                'moneda' => $inscripcion->moneda,
                'tipo_cambio' => $base['tipo_cambio'] ?? '1.000000',
                'monto' => $this->deCentavos($monto),
                'metodo_pago_id' => $metodo->getKey(),
                'forma_pago_sat' => $dinamicos['forma_pago_sat'] ?? $metodo->clave_forma_pago_sat,
                'observaciones' => isset($base['observaciones']) ? trim($base['observaciones']) : null,
                'fecha_movimiento' => $base['fecha_movimiento'] ?? null,
                'identificador_transaccion_externa' => $base['identificador_transaccion_externa'] ?? null,
                'estado' => Pago::ESTADO_CONFIRMADO,
                'created_by' => $usuarioId,
                'confirmed_by' => $usuarioId,
                'fecha_confirmacion' => now(),
            ]))->save();

            foreach ($ids as $cargoId) {
                $cargo = $cargos->get($cargoId);
                [$anterior, $posterior] = $saldos[$cargoId];
                $aplicacion = new PagoAplicacion();
                $aplicacion->forceFill([
                    'pago_id' => $pago->getKey(), 'cargo_id' => $cargoId,
                    'importe_aplicado' => $this->deCentavos($importes[$cargoId]),
                    'saldo_anterior' => $this->deCentavos($anterior),
                    'saldo_posterior' => $this->deCentavos($posterior),
                ])->save();
                $estado = $posterior === 0 ? Cargo::ESTADO_PAGADO
                    : ($cargo->fecha_vencimiento->lt(now()->startOfDay()) ? Cargo::ESTADO_VENCIDO : Cargo::ESTADO_PARCIAL);
                $cargo->forceFill(['saldo_pendiente' => $this->deCentavos($posterior), 'estado' => $estado])->save();
            }

            if ($comprobante instanceof UploadedFile) {
                $this->archivos->guardar($pago, $comprobante, $usuarioId, function (string $ruta) use (&$rutaArchivoNuevo): void {
                    $rutaArchivoNuevo = $ruta;
                });
            }

            return $pago->load(['aplicaciones', 'archivos']);
            });
        } catch (\Throwable $error) {
            // DB y filesystem no comparten transacción. Si el proceso continúa,
            // se compensa únicamente el objeto creado por este intento.
            if ($rutaArchivoNuevo !== null) $this->archivos->eliminarRutaNueva($rutaArchivoNuevo, $error);
            throw $error;
        }
    }

    private function normalizarAplicaciones(array $aplicaciones): array
    {
        $ids = [];
        $importes = [];
        foreach ($aplicaciones as $id => $importe) {
            $texto = is_int($id) ? (string) $id : $id;
            if (! is_string($texto) || preg_match('/^[1-9]\d*$/D', $texto) !== 1) {
                throw ValidationException::withMessages(['aplicaciones' => 'Los identificadores de cargos no son válidos.']);
            }
            $normalizado = (int) $texto;
            if (isset($importes[$normalizado])) {
                throw ValidationException::withMessages(['aplicaciones' => 'Un cargo no puede aparecer más de una vez.']);
            }
            $ids[] = $normalizado;
            $importes[$normalizado] = $this->aCentavos($importe, 'importe aplicado');
            if ($importes[$normalizado] <= 0) {
                throw ValidationException::withMessages(['aplicaciones' => 'Cada importe aplicado debe ser mayor a 0.00.']);
            }
        }
        sort($ids, SORT_NUMERIC);

        return [$ids, $importes];
    }

    private function aCentavos($valor, string $campo): int
    {
        if (is_int($valor)) {
            $valor = (string) $valor;
        }
        if (! is_string($valor) || preg_match('/^(?:0|[1-9]\d{0,9})(?:\.(\d{1,2}))?$/D', $valor, $partes) !== 1) {
            throw ValidationException::withMessages([$campo => "El {$campo} debe ser un decimal no ambiguo con máximo dos decimales."]);
        }
        $decimales = str_pad($partes[1] ?? '', 2, '0');

        return ((int) strstr($valor.'.', '.', true) * 100) + (int) $decimales;
    }

    private function deCentavos(int $centavos): string
    {
        return intdiv($centavos, 100).'.'.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}
