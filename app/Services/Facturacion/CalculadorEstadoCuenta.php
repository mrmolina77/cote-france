<?php

namespace App\Services\Facturacion;

use App\Models\Cargo;
use Illuminate\Support\Collection;

class CalculadorEstadoCuenta
{
    /**
     * Calculate the current ledger totals from charges, without using historical
     * payment applications as a second source of truth.
     */
    public function calcular(Collection $cargos): array
    {
        $total = $pagado = $pendiente = $vencido = 0;

        foreach ($cargos as $cargo) {
            if ($cargo->estado === Cargo::ESTADO_CANCELADO) {
                continue;
            }

            $totalCargo = max(0, $this->centavos($cargo->total));
            $saldo = max(0, $this->centavos($cargo->saldo_pendiente));
            $total += $totalCargo;
            $pagado += max(0, min($totalCargo, $totalCargo - $saldo));
            if ($saldo > 0) {
                $pendiente += $saldo;
                if ($cargo->estado === Cargo::ESTADO_VENCIDO) {
                    $vencido += $saldo;
                }
            }
        }

        return [
            'total_cargos' => $this->importe($total),
            'total_pagado' => $this->importe($pagado),
            'saldo_pendiente' => $this->importe($pendiente),
            'saldo_vencido' => $this->importe($vencido),
        ];
    }

    public function pagadoPorCargo(Cargo $cargo): string
    {
        return $this->valoresPresentacion($cargo)['pagado_actual'];
    }

    /**
     * Return display-safe values without mutating the persisted charge.
     */
    public function valoresPresentacion(Cargo $cargo): array
    {
        $total = max(0, $this->centavos($cargo->total));
        $saldo = min(max(0, $this->centavos($cargo->saldo_pendiente)), $total);

        return [
            'total_actual' => $this->importe($total),
            'saldo_actual' => $this->importe($saldo),
            'pagado_actual' => $this->importe($total - $saldo),
        ];
    }

    private function centavos($importe): int
    {
        $valor = trim((string) ($importe ?? '0'));
        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/D', $valor)) {
            return 0;
        }
        $negativo = str_starts_with($valor, '-');
        $valor = ltrim($valor, '-');
        [$enteros, $decimales] = array_pad(explode('.', $valor, 2), 2, '');
        $centavos = ((int) $enteros * 100) + (int) str_pad($decimales, 2, '0');

        return $negativo ? -$centavos : $centavos;
    }

    private function importe(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }
}
