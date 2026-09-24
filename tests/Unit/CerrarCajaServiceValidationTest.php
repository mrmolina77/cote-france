<?php

namespace Tests\Unit;

use App\Services\Facturacion\CerrarCajaService;
use App\Services\Facturacion\ReporteFinancieroService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class CerrarCajaServiceValidationTest extends TestCase
{
    /** @dataProvider importesInvalidos */
    public function test_rechaza_importes_contados_malformados($importe): void
    {
        $this->expectException(ValidationException::class);
        $this->service()->validarContados(['1|MXN' => $importe], ['1|MXN']);
    }

    public function importesInvalidos(): array
    {
        return [['-1.00'], ['1.001'], ['1e2'], ['NaN'], ['INF'], ['01.00'], ['1000000000000.00'], [null], [[]]];
    }

    public function test_normaliza_decimales_validos_sin_perder_precision(): void
    {
        $this->assertSame(['1|MXN' => '0.00', '2|USD' => '12.30'],
            $this->service()->validarContados(['1|MXN' => '0', '2|USD' => '12.3'], ['1|MXN', '2|USD']));
    }

    public function test_rechaza_combinaciones_adicionales_manipuladas(): void
    {
        $this->expectException(ValidationException::class);
        $this->service()->validarContados(['999|EUR' => '1.00'], ['1|MXN']);
    }

    private function service(): CerrarCajaService
    {
        return new CerrarCajaService($this->createMock(ReporteFinancieroService::class));
    }
}
