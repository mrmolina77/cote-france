<?php

namespace App\Services\Facturacion;

use InvalidArgumentException;

class CalculadorDescuentosCargoService
{
    private const MAXIMO_SUBTOTAL_CENTAVOS = 999999999999;

    /**
     * Calcula importes monetarios exactos usando únicamente aritmética entera.
     *
     * @return array{subtotal: string, descuento: string, recargo: string, impuestos: string, total: string, saldo_pendiente: string}
     */
    public function calcular($subtotal, $porcentajeDescuento, $porcentajeBeca): array
    {
        $subtotalCentavos = $this->aEnteroDecimal($subtotal, 'subtotal', self::MAXIMO_SUBTOTAL_CENTAVOS);
        $descuentoPuntosBase = $this->aEnteroDecimal($porcentajeDescuento ?? '0.00', 'descuento', 10000);
        $becaPuntosBase = $this->aEnteroDecimal($porcentajeBeca ?? '0.00', 'beca', 10000);

        if ($descuentoPuntosBase + $becaPuntosBase > 10000) {
            throw new InvalidArgumentException('La suma del descuento y la beca no puede superar 100.00.');
        }

        // Sumar la mitad del divisor implementa redondeo half-up para valores no negativos.
        $descuentoCentavos = intdiv(
            $subtotalCentavos * ($descuentoPuntosBase + $becaPuntosBase) + 5000,
            10000
        );
        $totalCentavos = $subtotalCentavos - $descuentoCentavos;

        return [
            'subtotal' => $this->formatearCentavos($subtotalCentavos),
            'descuento' => $this->formatearCentavos($descuentoCentavos),
            'recargo' => '0.00',
            'impuestos' => '0.00',
            'total' => $this->formatearCentavos($totalCentavos),
            'saldo_pendiente' => $this->formatearCentavos($totalCentavos),
        ];
    }

    private function aEnteroDecimal($valor, string $nombre, int $maximo): int
    {
        if (! is_string($valor) && ! is_int($valor)) {
            throw new InvalidArgumentException("El {$nombre} debe ser un decimal válido.");
        }

        $texto = (string) $valor;
        if (preg_match('/^(?:0|[1-9]\d*)(?:\.(\d{1,2}))?$/', $texto, $coincidencias) !== 1) {
            throw new InvalidArgumentException("El {$nombre} debe ser un decimal no negativo con máximo dos decimales.");
        }

        $partes = explode('.', $texto, 2);
        $decimales = str_pad($partes[1] ?? '', 2, '0');
        $entero = (int) $partes[0];
        if ($entero > intdiv($maximo, 100)) {
            throw new InvalidArgumentException("El {$nombre} está fuera del rango permitido.");
        }

        $resultado = $entero * 100 + (int) $decimales;
        if ($resultado > $maximo) {
            throw new InvalidArgumentException("El {$nombre} está fuera del rango permitido.");
        }

        return $resultado;
    }

    private function formatearCentavos(int $centavos): string
    {
        return intdiv($centavos, 100).'.'.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}
