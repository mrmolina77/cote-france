<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowReportes;
use App\Models\CierreCaja;
use App\Models\MetodoPago;
use App\Services\Facturacion\CerrarCajaService;
use App\Services\Facturacion\ReporteFinancieroService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Mockery;

class CierreCajaTest extends InscripcionesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_09_23_000001_create_cierres_caja_table.php'))->up();
        (require database_path('migrations/2026_09_24_000001_create_cierre_caja_movimientos_table.php'))->up();
    }

    public function test_caja_solo_consulta_y_cierra_su_identidad_aunque_manipule_livewire(): void
    {
        $caja = $this->user('caja');
        $otro = $this->user('caja');
        $component = Livewire::actingAs($caja)->test(ShowReportes::class)
            ->set('reporte', 'cierre')->set('cajeroId', (string) $otro->id);

        $this->assertSame($caja->id, $component->viewData('cajeroSeleccionado'));
        $service = app(CerrarCajaService::class);
        $this->expectException(AuthorizationException::class);
        $service->cerrar($caja, $otro->id, '2026-09-10', [], null);
    }

    public function test_admin_puede_cerrar_otra_caja_contabilidad_no_y_el_cierre_es_unico_e_inmutable(): void
    {
        $admin = $this->user('admin');
        $caja = $this->user('caja');
        $contabilidad = $this->user('contabilidad');
        $service = app(CerrarCajaService::class);

        $cierre = $service->cerrar($admin, $caja->id, '2026-09-10', [], 'Sin movimientos');
        $this->assertSame($caja->id, $cierre->cajero_id);
        $this->assertSame($admin->id, $cierre->cerrado_por);
        try {
            $service->cerrar($admin, $caja->id, '2026-09-10', [], null);
            $this->fail('No se debe reemplazar un cierre existente.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('fechaCaja', $exception->errors());
        }
        try {
            $cierre->update(['observaciones' => 'alterado']);
            $this->fail('El cierre debe ser inmutable.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('inmutable', $exception->getMessage());
        }
        $this->expectException(AuthorizationException::class);
        $service->cerrar($contabilidad, $caja->id, '2026-09-11', [], null);
    }

    public function test_exportacion_antes_del_cierre_se_identifica_como_pre_cierre_y_contabilidad_puede_consultarla(): void
    {
        $contabilidad = $this->user('contabilidad');
        $caja = $this->user('caja');
        $url = route('facturacion.reportes.exportar', ['tipo' => 'cierre', 'formato' => 'csv'])
            .'?fecha=2026-09-10&cajero_id='.$caja->id;

        $csv = $this->actingAs($contabilidad)->get($url)->assertOk()->streamedContent();
        $this->assertStringContainsString('PRE-CIERRE, AÚN NO CERRADO', $csv);
    }

    public function test_snapshot_conserva_movimientos_totales_conteos_y_exportacion_definitiva(): void
    {
        $admin = $this->user('admin');
        $caja = $this->user('caja');
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $responsable = DB::table('responsables_pago')->insertGetId(['tipo' => 'alumno', 'nombre_razon_social' => 'Responsable', 'created_at' => now(), 'updated_at' => now()]);
        $metodo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $pago = DB::table('pagos')->insertGetId(['folio' => 'SNAP-1', 'inscripciones_id' => $inscripcion->getKey(), 'prospectos_id' => $prospecto->getKey(),
            'responsable_pago_id' => $responsable, 'fecha_pago' => '2026-09-10 10:00:00', 'zona_horaria' => config('app.timezone'),
            'moneda' => 'MXN', 'monto' => '10.10', 'metodo_pago_id' => $metodo->getKey(), 'estado' => 'confirmado',
            'created_by' => $admin->id, 'confirmed_by' => $caja->id, 'created_at' => now(), 'updated_at' => now()]);
        $key = $metodo->getKey().'|MXN';

        $cierre = app(CerrarCajaService::class)->cerrar($admin, $caja->id, '2026-09-10', [$key => '10.30'], null);
        $this->assertSame([$key => '10.10'], $cierre->totales_esperados);
        $this->assertSame([$key => '10.30'], $cierre->importes_contados);
        $this->assertSame([$key => '0.20'], $cierre->diferencias);
        $this->assertDatabaseHas('cierre_caja_movimientos', ['cierre_caja_id' => $cierre->getKey(), 'pago_id' => $pago, 'folio' => 'SNAP-1', 'importe' => '10.10']);

        DB::table('pagos')->where('pago_id', $pago)->update(['monto' => '999.00', 'folio' => 'CAMBIADO']);
        $csv = $this->actingAs($admin)->get(route('facturacion.reportes.exportar', ['tipo' => 'cierre', 'formato' => 'csv']).'?fecha=2026-09-10&cajero_id='.$caja->id)->streamedContent();
        $this->assertStringContainsString('CIERRE DEFINITIVO', $csv);
        $this->assertStringContainsString('SNAP-1', $csv);
        $this->assertStringNotContainsString('CAMBIADO', $csv);
        $this->assertStringNotContainsString('999.00', $csv);
    }

    public function test_exige_conteo_para_movimiento_con_neto_cero_y_admite_combinaciones_configuradas_sin_movimiento(): void
    {
        $admin = $this->user('admin');
        $caja = $this->user('caja');
        $metodo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        config()->set('facturacion.monedas_permitidas', ['MXN', 'USD']);
        $reportes = Mockery::mock(ReporteFinancieroService::class);
        $reportes->shouldReceive('ventana')->andReturn([now()->startOfDay(), now()->addDay()->startOfDay()]);
        $reportes->shouldReceive('diario')->andReturn($this->resumen([['metodo_id' => $metodo->getKey(), 'metodo' => 'Efectivo', 'moneda' => 'MXN', 'bruto' => '5.00', 'ajustes' => '5.00', 'neto' => '0.00', 'cantidad' => 2]]));
        $reportes->shouldReceive('combinacionesContables')->andReturn(collect([
            $metodo->getKey().'|MXN' => ['neto' => '0.00', 'cantidad' => 2],
            $metodo->getKey().'|USD' => ['neto' => '0.00', 'cantidad' => 0],
        ]));
        $service = new CerrarCajaService($reportes);
        try {
            $service->cerrar($admin, $caja->id, '2026-09-10', [$metodo->getKey().'|USD' => '0'], null);
            $this->fail('Debe exigirse el conteo de toda combinación con movimientos.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('contados.'.$metodo->getKey().'|MXN', $exception->errors());
        }
        $this->assertDatabaseCount('cierres_caja', 0);
    }

    public function test_fallo_al_persistir_detalle_revierte_el_cierre_completo(): void
    {
        $admin = $this->user('admin');
        $caja = $this->user('caja');
        $reportes = Mockery::mock(ReporteFinancieroService::class);
        $reportes->shouldReceive('ventana')->andReturn([now()->startOfDay(), now()->addDay()->startOfDay()]);
        $summary = $this->resumen([]);
        $summary['pagos'] = LazyCollection::make(function () { throw new \RuntimeException('fallo inyectado'); yield; });
        $reportes->shouldReceive('diario')->andReturn($summary);
        $reportes->shouldReceive('combinacionesContables')->andReturn(collect());

        try {
            (new CerrarCajaService($reportes))->cerrar($admin, $caja->id, '2026-09-10', [], null);
            $this->fail('El fallo inyectado debía propagarse.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('fallo inyectado', $exception->getMessage());
        }
        $this->assertDatabaseCount('cierres_caja', 0);
        $this->assertDatabaseCount('cierre_caja_movimientos', 0);
    }

    private function resumen(array $totales): array
    {
        return ['inicio' => now()->startOfDay(), 'fin' => now()->addDay()->startOfDay(), 'totales' => collect($totales),
            'pagos' => LazyCollection::make([]), 'eventos' => LazyCollection::make([])];
    }
}
