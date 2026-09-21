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
            ['1.01', 'Un peso con uno centavo'],
            ['15.05', 'Quince pesos con cinco centavos'],
            ['1100.10', 'Mil cien pesos con diez centavos'],
            ['1000001.99', 'Un millón un pesos con noventa y nueve centavos'],
        ];
    }

    public function test_moneda_no_mxn_se_presenta_sin_asumir_nombres(): void
    {
        $this->assertSame('Dos USD con 05/100', (new ImporteEnLetrasService())->convertir('2.05', 'usd'));
    }
}
