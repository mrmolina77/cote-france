<?php

namespace App\Services\Facturacion;

use App\Models\Cargo;
use App\Models\Pago;
use App\Models\PagoAplicacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CancelarPagoService
{
    public function cancelar(int $pagoId, string $motivo, int $usuarioId): Pago
    {
        $motivo = trim($motivo);
        Validator::make(['motivo' => $motivo], [
            'motivo' => ['required', 'string', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use ($pagoId, $motivo, $usuarioId) {
            /** @var Pago|null $pago */
            $pago = Pago::query()->whereKey($pagoId)->lockForUpdate()->first();
            if (! $pago) {
                throw ValidationException::withMessages(['pago_id' => 'El pago seleccionado no está disponible.']);
            }
            if ($pago->estado !== Pago::ESTADO_CONFIRMADO) {
                throw ValidationException::withMessages(['pago_id' => 'El pago ya no puede cancelarse.']);
            }

            $aplicaciones = PagoAplicacion::query()
                ->where('pago_id', $pago->getKey())
                ->orderBy('cargo_id')
                ->get();
            if ($aplicaciones->isEmpty() || $aplicaciones->contains(fn (PagoAplicacion $aplicacion) =>
                (int) $aplicacion->pago_id !== (int) $pago->getKey()
            )) {
                throw ValidationException::withMessages(['pago_id' => 'Las aplicaciones del pago no son consistentes.']);
            }

            $cargoIds = $aplicaciones->pluck('cargo_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            $cargos = Cargo::query()
                ->whereIn('cargo_id', $cargoIds)
                ->orderBy('cargo_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('cargo_id');
            if ($cargos->count() !== count($cargoIds)) {
                throw ValidationException::withMessages(['pago_id' => 'Uno o más cargos del pago ya no están disponibles.']);
            }

            $restauraciones = [];
            $totalAplicado = 0;
            foreach ($aplicaciones as $aplicacion) {
                /** @var Cargo|null $cargo */
                $cargo = $cargos->get((int) $aplicacion->cargo_id);
                if (! $cargo || $cargo->estado === Cargo::ESTADO_CANCELADO) {
                    throw ValidationException::withMessages(['pago_id' => 'Uno o más cargos no permiten restaurar el pago de forma segura.']);
                }
                $saldo = $this->aCentavos($cargo->saldo_pendiente);
                $importe = $this->aCentavos($aplicacion->importe_aplicado);
                $saldoAnterior = $this->aCentavos($aplicacion->saldo_anterior);
                $saldoPosterior = $this->aCentavos($aplicacion->saldo_posterior);
                $total = $this->aCentavos($cargo->total);
                $restaurado = $saldo + $importe;
                if ($importe <= 0 || $saldo < 0 || $total < 0
                    || $saldoAnterior - $importe !== $saldoPosterior
                    || $saldoAnterior > $total || $saldoPosterior < 0
                    || $restaurado < 0 || $restaurado > $total) {
                    throw ValidationException::withMessages(['pago_id' => 'Los saldos del pago no permiten una restauración segura.']);
                }
                $totalAplicado += $importe;
                $restauraciones[] = [$cargo, $restaurado, $total];
            }
            if ($totalAplicado !== $this->aCentavos($pago->monto)) {
                throw ValidationException::withMessages(['pago_id' => 'Las aplicaciones del pago no coinciden con su importe.']);
            }

            foreach ($restauraciones as [$cargo, $restaurado, $total]) {
                $estado = $this->estadoRestaurado($cargo, $restaurado, $total);
                $cargo->forceFill([
                    'saldo_pendiente' => $this->deCentavos($restaurado),
                    'estado' => $estado,
                ])->save();
            }

            $pago->forceFill([
                'estado' => Pago::ESTADO_CANCELADO,
                'cancelled_by' => $usuarioId,
                'fecha_cancelacion' => now(),
                'motivo_cancelacion' => $motivo,
            ])->save();

            return $pago->load(['aplicaciones.cargo', 'cancelledBy']);
        });
    }

    private function estadoRestaurado(Cargo $cargo, int $saldo, int $total): string
    {
        if ($saldo === 0) {
            return Cargo::ESTADO_PAGADO;
        }
        if ($cargo->fecha_vencimiento->lt(now()->startOfDay())) {
            return Cargo::ESTADO_VENCIDO;
        }

        return $saldo === $total ? Cargo::ESTADO_PENDIENTE : Cargo::ESTADO_PARCIAL;
    }

    private function aCentavos($valor): int
    {
        if (! is_string($valor) || preg_match('/^(?:0|[1-9]\d{0,9})(?:\.(\d{1,2}))?$/D', $valor, $partes) !== 1) {
            throw ValidationException::withMessages(['pago_id' => 'Los importes almacenados no permiten una restauración segura.']);
        }

        return ((int) strstr($valor.'.', '.', true) * 100) + (int) str_pad($partes[1] ?? '', 2, '0');
    }

    private function deCentavos(int $centavos): string
    {
        return intdiv($centavos, 100).'.'.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}
