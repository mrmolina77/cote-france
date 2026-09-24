<?php

namespace Tests\Unit;

use App\Support\SimpleXlsx;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class SimpleXlsxTest extends TestCase
{
    /** @dataProvider dangerousCells */
    public function test_neutraliza_formulas_sin_alterar_texto_espanol(string $value, string $expected): void
    {
        $this->assertSame($expected, SimpleXlsx::safe($value));
    }

    public function dangerousCells(): array
    {
        return [
            ['=SUM(A1:A2)', "'=SUM(A1:A2)"], ['+cmd', "'+cmd"], ['-2', "'-2"], ['@formula', "'@formula"],
            ["\tformula", "'\tformula"], ["\rformula", "'\rformula"], ['José pagó €10', 'José pagó €10'],
        ];
    }

    public function test_elimina_el_temporal_de_hoja_si_falla_la_generacion(): void
    {
        $before = glob(sys_get_temp_dir().'/reporte-sheet-*');
        $rows = (function () { yield ['primera']; throw new \RuntimeException('fallo esperado'); })();

        try {
            SimpleXlsx::create($rows);
            $this->fail('La excepción del generador debía propagarse.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('fallo esperado', $exception->getMessage());
        }

        $this->assertSame($before, glob(sys_get_temp_dir().'/reporte-sheet-*'));
    }

    public function test_escribe_incrementalmente_una_hoja_xlsx_legible_con_todas_las_filas(): void
    {
        $rows = (function () {
            yield ['Alumno', 'Monto'];
            for ($i = 1; $i <= 1205; $i++) yield ["José $i", $i === 501 ? '=2+2' : "$i.00"];
        })();

        $path = SimpleXlsx::create($rows);
        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($path));
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertNotFalse($xml);
        $sheet = simplexml_load_string($xml);
        $this->assertInstanceOf(\SimpleXMLElement::class, $sheet);
        $sheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $this->assertCount(1206, $sheet->xpath('//x:sheetData/x:row'));

        $firstName = $sheet->xpath('//x:c[@r="A2"]/x:is/x:t');
        $formula = $sheet->xpath('//x:c[@r="B502"]/x:is/x:t');
        $lastName = $sheet->xpath('//x:c[@r="A1206"]/x:is/x:t');

        $this->assertCount(1, $firstName);
        $this->assertCount(1, $formula);
        $this->assertCount(1, $lastName);
        $this->assertSame('José 1', (string) $firstName[0]);
        $this->assertSame("'=2+2", (string) $formula[0]);
        $this->assertSame('José 1205', (string) $lastName[0]);
        $this->assertSame([], glob(sys_get_temp_dir().'/reporte-sheet-*'));

        unlink($path);
    }
}
