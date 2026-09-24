<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Curso;
use App\Models\Grupo;
use App\Models\MetodoPago;
use App\Services\Facturacion\ReporteFinancieroService;
use Illuminate\Support\Facades\DB;

class ReportesFinancierosTest extends InscripcionesTestCase
{
    private $responsable;
    private $cajero;
    private $registrador;
    private $efectivo;
    private $deposito;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registrador = $this->user('venta');
        $this->cajero = $this->user('caja');
        $this->efectivo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $this->deposito = MetodoPago::where('clave', MetodoPago::DEPOSITO_BANCARIO)->firstOrFail();
        $this->responsable = DB::table('responsables_pago')->insertGetId([
            'tipo' => 'alumno', 'nombre_razon_social' => 'Responsable', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_grupo_curso_metodo_y_usuario_cuentan_solo_confirmados_una_vez(): void
    {
        [$p1, $c1, $g1] = $this->catalogs();
        $p2 = \App\Models\Prospecto::create(['prospectos_nombres' => 'Otro', 'prospectos_telefono1' => '5551111111']);
        $c2 = Curso::create(['cursos_descripcion' => 'Inglés', 'cursos_fecha_creacion' => '2026-08-16']);
        $g2 = Grupo::create(['grupo_nombre' => 'B2', 'modalidad_id' => 1]);
        $i1 = $this->enroll($p1, $c1, $g1);
        $i2 = $this->enroll($p2, $c2, $g2);
        $a = $this->pago($i1->getKey(), $p1->getKey(), 'CONF-1', '100.00', 'confirmado', $this->efectivo->getKey(), $this->cajero->id);
        $b = $this->pago($i2->getKey(), $p2->getKey(), 'CONF-2', '75.00', 'confirmado', $this->deposito->getKey(), null);
        $this->pago($i1->getKey(), $p1->getKey(), 'BORR', '500.00', 'borrador', $this->efectivo->getKey(), $this->cajero->id);
        $this->pago($i1->getKey(), $p1->getKey(), 'CANC', '600.00', 'cancelado', $this->efectivo->getKey(), $this->cajero->id);
        $this->pago($i1->getKey(), $p1->getKey(), 'REEM', '700.00', 'reembolsado', $this->efectivo->getKey(), $this->cajero->id);
        $cargo1 = $this->cargo($i1->getKey(), 2026, 8, '100.00', '0.00', '2026-08-01');
        $cargo2 = $this->cargo($i1->getKey(), 2026, 9, '100.00', '0.00', '2026-09-01');
        $this->aplicar($a, $cargo1, '40.00');
        $this->aplicar($a, $cargo2, '30.00');

        $service = app(ReporteFinancieroService::class);
        $f = ['desde' => '2026-09-01', 'hasta' => '2026-09-30'];
        $this->assertRows($service->agrupacion('grupo', $f), [['A1', 1, '100.00'], ['B2', 1, '75.00']]);
        $this->assertRows($service->agrupacion('curso', $f), [['Francés', 1, '100.00'], ['Inglés', 1, '75.00']]);
        $this->assertRows($service->agrupacion('metodo', $f), [['Depósito bancario', 1, '75.00'], ['Efectivo', 1, '100.00']]);

        $usuarios = $service->agrupacion('usuario', $f)->keyBy('dimension');
        $this->assertSame('175.00', $usuarios['Registró: '.$this->registrador->name]['monto']);
        $this->assertSame('100.00', $usuarios['Cajero confirmó: '.$this->cajero->name]['monto']);
        $this->assertSame('75.00', $usuarios['Cajero confirmó: Sin cajero registrado']['monto']);
        $this->assertSame([$a, $b], DB::table('pagos')->where('estado', 'confirmado')->orderBy('pago_id')->pluck('pago_id')->all());
    }

    public function test_periodo_distribuye_aplicaciones_y_anticipo_sin_duplicar_el_pago(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $pago = $this->pago($inscripcion->getKey(), $prospecto->getKey(), 'PER-1', '100.00', 'confirmado', $this->efectivo->getKey(), $this->cajero->id);
        $this->aplicar($pago, $this->cargo($inscripcion->getKey(), 2026, 8, '40.00', '0.00', '2026-08-01'), '40.00');
        $this->aplicar($pago, $this->cargo($inscripcion->getKey(), 2026, 9, '25.00', '0.00', '2026-09-01'), '25.00');

        $rows = app(ReporteFinancieroService::class)->agrupacion('periodo', ['desde' => '2026-09-01', 'hasta' => '2026-09-30'])->keyBy('dimension');
        $this->assertSame('40.00', $rows['2026-08']['monto']);
        $this->assertSame('25.00', $rows['2026-09']['monto']);
        $this->assertSame('35.00', $rows['Anticipo / sin asignación']['monto']);
        $this->assertSame('100.00', (string) $rows->pluck('monto')->reduce(fn ($sum, $amount) => $sum->plus($amount), \Brick\Math\BigDecimal::zero())->toScale(2));
    }

    public function test_vencidos_respeta_saldo_estado_y_limite_estricto(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $i = $this->enroll($prospecto, $curso, $grupo);
        $incluido = $this->cargo($i->getKey(), 2026, 8, '100.00', '30.00', '2026-09-09');
        $this->cargo($i->getKey(), 2026, 9, '100.00', '30.00', '2026-09-10');
        $this->cargo($i->getKey(), 2026, 7, '100.00', '0.00', '2026-09-01');
        $this->cargo($i->getKey(), 2026, 6, '100.00', '30.00', '2026-09-01', 'cancelado');

        $rows = app(ReporteFinancieroService::class)->vencidos(['corte' => '2026-09-10']);
        $this->assertSame([$incluido], $rows->pluck('cargo_id')->all());
        $this->assertSame('70.00', $rows->first()->pagado);
    }

    public function test_eventos_no_inventan_actor_de_reembolso_y_diario_usa_limites_locales(): void
    {
        config()->set('app.timezone', 'America/Mexico_City');
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $i = $this->enroll($prospecto, $curso, $grupo);
        $this->pago($i->getKey(), $prospecto->getKey(), 'START', '10.00', 'confirmado', $this->efectivo->getKey(), $this->cajero->id, '2026-09-10 00:00:00');
        $this->pago($i->getKey(), $prospecto->getKey(), 'END-IN', '20.00', 'confirmado', $this->efectivo->getKey(), $this->cajero->id, '2026-09-10 23:59:59');
        $this->pago($i->getKey(), $prospecto->getKey(), 'END-OUT', '40.00', 'confirmado', $this->efectivo->getKey(), $this->cajero->id, '2026-09-11 00:00:00');
        $cancelado = $this->pago($i->getKey(), $prospecto->getKey(), 'CANCEL', '5.00', 'cancelado', $this->efectivo->getKey(), $this->cajero->id);
        DB::table('pagos')->where('pago_id', $cancelado)->update(['cancelled_by' => $this->cajero->id, 'fecha_cancelacion' => '2026-09-10 15:00:00']);
        $reembolso = $this->pago($i->getKey(), $prospecto->getKey(), 'REFUND', '7.00', 'reembolsado', $this->efectivo->getKey(), $this->cajero->id);
        DB::table('pagos')->where('pago_id', $reembolso)->update(['cancelled_by' => $this->cajero->id, 'fecha_reembolso' => '2026-09-10 16:00:00']);

        $service = app(ReporteFinancieroService::class);
        [$inicio, $fin] = $service->ventana('2026-09-10');
        $this->assertSame('2026-09-10 00:00:00', $inicio->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-11 00:00:00', $fin->format('Y-m-d H:i:s'));
        $diario = $service->diario('2026-09-10', $this->cajero->id);
        $this->assertSame(['START', 'END-IN'], $diario['pagos']->pluck('folio')->all());
        $this->assertSame(['CANCEL'], $diario['eventos']->pluck('folio')->all(), 'Un reembolso carece de actor persistido y no pertenece a una caja nominal.');
        $eventos = $service->eventos(['desde' => '2026-09-10', 'hasta' => '2026-09-10', 'tipo' => 'todos']);
        $this->assertSame(['CANCEL', 'REFUND'], $eventos->pluck('folio')->all());
    }

    private function pago(int $inscripcion, int $prospecto, string $folio, string $monto, string $estado, int $metodo, ?int $confirmedBy, string $fecha = '2026-09-10 12:00:00'): int
    {
        return DB::table('pagos')->insertGetId(['folio' => $folio, 'inscripciones_id' => $inscripcion, 'prospectos_id' => $prospecto,
            'responsable_pago_id' => $this->responsable, 'fecha_pago' => $fecha, 'zona_horaria' => config('app.timezone'), 'moneda' => 'MXN',
            'monto' => $monto, 'metodo_pago_id' => $metodo, 'estado' => $estado, 'created_by' => $this->registrador->id,
            'confirmed_by' => $confirmedBy, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function cargo(int $inscripcion, int $anio, int $mes, string $total, string $saldo, string $vence, string $estado = 'pendiente'): int
    {
        return Cargo::create(['inscripciones_id' => $inscripcion, 'concepto_cobro_id' => ConceptoCobro::where('clave', 'MENSUALIDAD')->value('concepto_cobro_id'),
            'periodo_anio' => $anio, 'periodo_mes' => $mes, 'fecha_emision' => '2026-08-01', 'fecha_vencimiento' => $vence,
            'moneda' => 'MXN', 'subtotal' => $total, 'total' => $total, 'saldo_pendiente' => $saldo, 'estado' => $estado, 'origen' => 'manual'])->getKey();
    }

    private function aplicar(int $pago, int $cargo, string $monto): void
    {
        DB::table('pago_aplicaciones')->insert(['pago_id' => $pago, 'cargo_id' => $cargo, 'importe_aplicado' => $monto,
            'saldo_anterior' => $monto, 'saldo_posterior' => '0.00', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function assertRows($rows, array $expected): void
    {
        $actual = $rows->map(fn ($row) => [$row['dimension'], $row['cantidad'], $row['monto']])->sort()->values()->all();
        sort($expected);
        $this->assertSame($expected, $actual);
    }
}
