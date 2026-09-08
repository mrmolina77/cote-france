<?php

namespace Tests\Unit;

use App\Services\Facturacion\CalculadorDescuentosCargoService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CalculadorDescuentosCargoServiceTest extends TestCase
{
    /** @dataProvider casosValidos */
    public function test_calcula_importes_exactos($subtotal, $descuento, $beca, string $reduccion, string $total): void
    {
        $resultado = (new CalculadorDescuentosCargoService())->calcular($subtotal, $descuento, $beca);

        $this->assertSame([
            'subtotal' => is_int($subtotal) ? $subtotal.'.00' : $subtotal,
            'descuento' => $reduccion,
            'recargo' => '0.00',
            'impuestos' => '0.00',
            'total' => $total,
            'saldo_pendiente' => $total,
        ], $resultado);
        foreach ($resultado as $importe) {
            $this->assertIsString($importe);
            $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $importe);
        }
    }

    public function casosValidos(): array
    {
        return [
            'sin ajustes' => [1000, '0.00', '0.00', '0.00', '1000.00'],
            'solo descuento' => ['1000.00', '10.00', '0.00', '100.00', '900.00'],
            'solo beca' => ['1000.00', '0.00', '20.00', '200.00', '800.00'],
            'combinados' => ['1234.56', '20.00', '30.00', '617.28', '617.28'],
            'redondeo' => ['99.99', '13.33', '20.00', '33.33', '66.66'],
            'medio centavo half-up' => ['0.01', '50.00', '0.00', '0.01', '0.00'],
            'bonificación total' => ['2500.00', '40.00', '60.00', '2500.00', '0.00'],
            'porcentajes nulos' => ['1.20', null, null, '0.00', '1.20'],
            'máximo' => ['9999999999.99', '100.00', '0.00', '9999999999.99', '0.00'],
        ];
    }

    /** @dataProvider casosInvalidos */
    public function test_rechaza_entradas_invalidas($subtotal, $descuento, $beca): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CalculadorDescuentosCargoService())->calcular($subtotal, $descuento, $beca);
    }

    public function casosInvalidos(): array
    {
        return [
            ['-1.00', '0.00', '0.00'], ['1.001', '0.00', '0.00'], ['10000000000.00', '0.00', '0.00'],
            ['1e2', '0.00', '0.00'], ['1,000.00', '0.00', '0.00'], ['10,00', '0.00', '0.00'],
            ['NaN', '0.00', '0.00'], ['INF', '0.00', '0.00'], ['10abc', '0.00', '0.00'],
            ['10.00', '-1.00', '0.00'], ['10.00', '100.01', '0.00'], ['10.00', '60.00', '40.01'],
            [10.0, '0.00', '0.00'], ['', '0.00', '0.00'], [' 10.00', '0.00', '0.00'],
        ];
    }
}
