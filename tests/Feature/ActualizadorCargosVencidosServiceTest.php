<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Inscripcion;
use App\Services\Facturacion\ActualizadorCargosVencidosService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ActualizadorCargosVencidosServiceTest extends InscripcionesTestCase
{
    private ActualizadorCargosVencidosService $service;
    private Inscripcion $inscripcion;
    private ConceptoCobro $concepto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ActualizadorCargosVencidosService();
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $this->inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $this->concepto = ConceptoCobro::where('clave', 'MENSUALIDAD')->firstOrFail();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_actualiza_solo_cargos_realmente_vencidos_y_devuelve_el_total_exacto(): void
    {
        $pendiente = $this->cargo(Cargo::ESTADO_PENDIENTE, '2026-09-07', '120.00');
        $parcial = $this->cargo(Cargo::ESTADO_PARCIAL, '2026-09-01', '35.40');
        $hoy = $this->cargo(Cargo::ESTADO_PENDIENTE, '2026-09-08', '100.00');
        $futuro = $this->cargo(Cargo::ESTADO_PARCIAL, '2026-09-09', '50.00');
        $pagado = $this->cargo(Cargo::ESTADO_PAGADO, '2026-09-01', '0.00');
        $cancelado = $this->cargo(Cargo::ESTADO_CANCELADO, '2026-09-01', '100.00');
        $vencido = $this->cargo(Cargo::ESTADO_VENCIDO, '2026-09-01', '80.00');
        $sinSaldo = $this->cargo(Cargo::ESTADO_PENDIENTE, '2026-09-01', '0.00');
        [$otroProspecto, $otroCurso, $otroGrupo] = $this->catalogs();
        $otraInscripcion = $this->enroll($otroProspecto, $otroCurso, $otroGrupo);
        $ajenoNoElegible = $this->cargo(Cargo::ESTADO_PAGADO, '2026-01-01', '0.00', $otraInscripcion);
        $protegidos = $parcial->fresh()->getRawOriginal();

        $this->assertSame(2, $this->service->actualizar(CarbonImmutable::parse('2026-09-08', 'America/Mexico_City')));

        $this->assertSame(Cargo::ESTADO_VENCIDO, $pendiente->fresh()->estado);
        $this->assertSame(Cargo::ESTADO_VENCIDO, $parcial->fresh()->estado);
        $this->assertSame('35.40', $parcial->fresh()->saldo_pendiente);
        foreach ([$hoy, $futuro, $pagado, $cancelado, $vencido, $sinSaldo, $ajenoNoElegible] as $cargo) {
            $this->assertSame($cargo->estado, $cargo->fresh()->estado);
            $this->assertSame($cargo->updated_at->format('Y-m-d H:i:s'), $cargo->fresh()->updated_at->format('Y-m-d H:i:s'));
        }
        $actual = $parcial->fresh()->getRawOriginal();
        foreach (array_diff(array_keys($protegidos), ['estado', 'updated_at']) as $campo) {
            $this->assertSame($protegidos[$campo], $actual[$campo], "El campo {$campo} fue alterado.");
        }
    }

    public function test_es_idempotente_y_usa_la_fecha_actual_en_la_zona_horaria_de_la_aplicacion(): void
    {
        config()->set('app.timezone', 'America/Mexico_City');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-08 00:05:00', 'America/Mexico_City'));
        $this->cargo(Cargo::ESTADO_PENDIENTE, '2026-09-07', '1.00');
        $this->cargo(Cargo::ESTADO_PENDIENTE, '2026-09-08', '1.00');

        $this->assertSame(1, $this->service->actualizar());
        $this->assertSame(0, $this->service->actualizar());
    }

    public function test_una_excepcion_no_deja_actualizaciones_parciales(): void
    {
        $primero = $this->cargo(Cargo::ESTADO_PENDIENTE, '2026-09-01', '10.00');
        $segundo = $this->cargo(Cargo::ESTADO_PENDIENTE, '2026-09-01', '10.00');
        DB::unprepared("CREATE TRIGGER impedir_segundo BEFORE UPDATE ON cargos WHEN OLD.cargo_id = {$segundo->getKey()} BEGIN SELECT RAISE(ABORT, 'fallo controlado'); END");

        try {
            $this->service->actualizar(CarbonImmutable::parse('2026-09-08'));
            $this->fail('Se esperaba una excepción de base de datos.');
        } catch (\Throwable $exception) {
            $this->assertStringContainsString('fallo controlado', $exception->getMessage());
        }

        $this->assertSame(Cargo::ESTADO_PENDIENTE, $primero->fresh()->estado);
        $this->assertSame(Cargo::ESTADO_PENDIENTE, $segundo->fresh()->estado);
    }

    private function cargo(string $estado, string $vencimiento, string $saldo, ?Inscripcion $inscripcion = null): Cargo
    {
        return Cargo::create([
            'inscripciones_id' => ($inscripcion ?? $this->inscripcion)->getKey(),
            'concepto_cobro_id' => $this->concepto->getKey(),
            'periodo_anio' => 2026, 'periodo_mes' => 9,
            'fecha_emision' => '2026-08-20', 'fecha_vencimiento' => $vencimiento,
            'moneda' => 'MXN', 'subtotal' => '150.00', 'descuento' => '20.00',
            'recargo' => '3.00', 'impuestos' => '7.00', 'total' => '140.00',
            'saldo_pendiente' => $saldo, 'estado' => $estado, 'origen' => Cargo::ORIGEN_MANUAL,
            'clave_idempotencia' => uniqid('cargo-', true), 'observaciones' => 'No alterar',
        ]);
    }
}
