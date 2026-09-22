<?php

namespace Tests\Feature;

use App\Services\Facturacion\ImporteEnLetrasService;
use PHPUnit\Framework\TestCase;

class ImporteEnLetrasServiceTest extends TestCase
{
    /** @dataProvider importes */
    public function test_convierte_importes_sin_aritmetica_flotante(string $importe, string $esperado): void
    {
        $this->assertSame($esperado, (new ImporteEnLetrasService())->convertir($importe));
    }

    public function importes(): array
    {
        return [
            ['0.00', 'Cero pesos con cero centavos'],
            ['1.00', 'Un peso con cero centavos'],
            ['1.01', 'Un peso con uno centavo'],
            ['2.05', 'Dos pesos con cinco centavos'],
            ['10.10', 'Diez pesos con diez centavos'],
            ['99.99', 'Noventa y nueve pesos con noventa y nueve centavos'],
            ['100.00', 'Cien pesos con cero centavos'],
            ['101.00', 'Ciento un pesos con cero centavos'],
            ['15.05', 'Quince pesos con cinco centavos'],
            ['1100.10', 'Mil cien pesos con diez centavos'],
            ['1000001.99', 'Un millón un pesos con noventa y nueve centavos'],
            ['1010000100.00', 'Mil diez millones cien pesos con cero centavos'],
        ];
    }

    public function test_moneda_no_mxn_se_presenta_sin_asumir_nombres(): void
    {
        $this->assertSame('Dos USD con 05/100', (new ImporteEnLetrasService())->convertir('2.05', 'usd'));
    }

    /** @dataProvider importesInvalidos */
    public function test_rechaza_importes_ambiguos_o_fuera_del_rango(string $importe): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('decimal no negativo con máximo dos decimales');
        (new ImporteEnLetrasService())->convertir($importe);
    }

    public function importesInvalidos(): array
    {
        return array_map(fn ($valor) => [$valor], [
            '-1.00', '1.001', '1,100.10', ' 1.00', '1.00 ', '+1.00', '01.00',
            'NaN', 'INF', '', 'texto', '10000000000000.00',
        ]);
    }
}
