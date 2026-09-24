<?php

namespace Tests\Unit;

use App\Support\SimpleXlsx;
use Tests\TestCase;
use ZipArchive;

class SimpleXlsxTest extends TestCase
{
    public function test_formula_cells_are_neutralized_and_xlsx_is_valid_zip(): void
    {
        $this->assertSame("'=SUM(A1:A2)",SimpleXlsx::safe('=SUM(A1:A2)'));
        $this->assertSame("'+cmd",SimpleXlsx::safe('+cmd'));
        $path=SimpleXlsx::create([['Alumno','Monto'],['José','=2+2']]); $zip=new ZipArchive();
        $this->assertTrue($zip->open($path)===true); $sheet=$zip->getFromName('xl/worksheets/sheet1.xml'); $zip->close(); unlink($path);
        $this->assertStringContainsString('José',$sheet); $this->assertStringContainsString("'=2+2",$sheet);
    }
}
