<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class ExportarReporteTest extends InscripcionesTestCase
{
    public function test_export_route_is_protected_by_the_server_gate(): void
    {
        $url = $this->url('csv');

        $this->get($url)->assertRedirect('/login');
        $this->actingAs($this->user('caja'))->get($url)->assertForbidden();
        $this->actingAs($this->user('venta'))->get($url)->assertForbidden();
        $this->actingAs($this->user('contabilidad'))->get($url)->assertOk();
    }

    public function test_csv_and_xlsx_stream_every_row_across_small_batches_and_neutralize_formulas(): void
    {
        config()->set('facturacion.export_chunk_size', 2);
        $accountant = $this->user('contabilidad');
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $curso->update(['cursos_descripcion' => '=CURSO peligroso']);
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $responsable = DB::table('responsables_pago')->insertGetId([
            'tipo' => 'alumno', 'prospectos_id' => $prospecto->getKey(),
            'nombre_razon_social' => 'Álvaro normal', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $metodo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();

        foreach (range(1, 7) as $number) {
            DB::table('pagos')->insert([
                'folio' => sprintf('LOTE-%02d', $number),
                'inscripciones_id' => $inscripcion->getKey(), 'prospectos_id' => $prospecto->getKey(),
                'responsable_pago_id' => $responsable, 'fecha_pago' => "2026-09-1{$number} 12:00:00",
                'zona_horaria' => config('app.timezone'), 'moneda' => 'MXN', 'monto' => $number.'.00',
                'metodo_pago_id' => $metodo->getKey(), 'estado' => 'confirmado',
                'created_by' => $accountant->getKey(), 'confirmed_by' => $accountant->getKey(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $csv = $this->actingAs($accountant)->get($this->url('csv'));
        $csv->assertOk();
        $body = $csv->streamedContent();
        $this->assertStringContainsString("'=CURSO peligroso", $body);
        $this->assertStringContainsString('7,MXN,28.00', $body);

        $xlsx = $this->actingAs($accountant)->get($this->url('xlsx'));
        $xlsx->assertOk();
        $path = $xlsx->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $this->assertIsString($sheet);
        $this->assertStringContainsString('&apos;=CURSO peligroso', $sheet);
        $this->assertStringContainsString('28.00', $sheet);
    }

    public function test_csv_and_xlsx_preserve_the_same_headers_when_there_are_no_results(): void
    {
        $accountant = $this->user('contabilidad');
        $csv = $this->actingAs($accountant)->get($this->url('csv'))->streamedContent();
        $this->assertStringContainsString('Dimensión,"Tipo de usuario","Cantidad de pagos",Moneda,Monto', $csv);

        $response = $this->actingAs($accountant)->get($this->url('xlsx'));
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $sheet = simplexml_load_string($xml);
        $this->assertInstanceOf(\SimpleXMLElement::class, $sheet);
        $sheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $values = array_map('strval', $sheet->xpath('//x:row[last()]/x:c/x:is/x:t'));
        $this->assertSame(['Dimensión', 'Tipo de usuario', 'Cantidad de pagos', 'Moneda', 'Monto'], $values);
    }

    private function url(string $format): string
    {
        return route('facturacion.reportes.exportar', ['tipo' => 'curso', 'formato' => $format]).'?'.http_build_query([
            'desde' => '2026-09-01', 'hasta' => '2026-09-30', 'corte' => '2026-09-30',
        ]);
    }
}
