<?php

namespace Tests\Unit;

use App\Http\Livewire\ShowReportes;
use PHPUnit\Framework\TestCase;

class ShowReportesMoneyTest extends TestCase
{
    /** @dataProvider diferencias */
    public function test_la_diferencia_visible_usa_aritmetica_decimal(string $contado, string $esperado, string $diferencia): void
    {
        $component = new ShowReportes();
        $component->contados = ['1|MXN' => $contado];

        $this->assertSame($diferencia, $component->diferenciaPreliminar('1|MXN', $esperado));
    }

    public function diferencias(): array
    {
        return [
            'decimales propensos a binario' => ['0.30', '0.10', '0.20'],
            'resta exacta' => ['0.30', '0.30', '0.00'],
            'diferencia negativa' => ['0.10', '0.30', '-0.20'],
            'importe grande permitido' => ['999999999999.99', '999999999999.79', '0.20'],
        ];
    }

    /** @dataProvider invalidos */
    public function test_no_presenta_una_diferencia_para_entradas_invalidas($valor): void
    {
        $component = new ShowReportes();
        $component->contados = ['1|MXN' => $valor];

        $this->assertNull($component->diferenciaPreliminar('1|MXN', '0.00'));
    }

    public function invalidos(): array
    {
        return [['0.001'], ['1e2'], ['NaN'], ['INF'], ['-1'], [null], [[]]];
    }
}
