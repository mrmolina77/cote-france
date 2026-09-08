<?php

namespace Tests\Feature;

use App\Exceptions\CargoManualInvalidoException;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Services\Facturacion\CreadorCargoManualService;

class CreadorCargoManualServiceTest extends InscripcionesTestCase
{
    private CreadorCargoManualService $service;
    private $inscripcion;
    private $concepto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CreadorCargoManualService();
        $this->inscripcion = $this->enroll(...$this->catalogs());
        $this->concepto = ConceptoCobro::create(['clave' => 'MATERIAL', 'nombre' => 'Material', 'activo' => true]);
    }

    public function test_creates_an_exact_manual_charge_with_period_traceability_and_server_values(): void
    {
        $user = $this->user('admin');
        $cargo = $this->service->crear($this->valid(['subtotal' => '1234.5', 'periodo_anio' => 2027, 'periodo_mes' => 1, 'observaciones' => ' Libro '] + ['created_by' => 999]), $user->getKey())->fresh();

        $this->assertInstanceOf(Cargo::class, $cargo);
        $this->assertSame($this->inscripcion->getKey(), $cargo->inscripciones_id);
        $this->assertSame($this->concepto->getKey(), $cargo->concepto_cobro_id);
        $this->assertSame(['1234.50', '1234.50', '1234.50'], [$cargo->subtotal, $cargo->total, $cargo->saldo_pendiente]);
        $this->assertSame(['0.00', '0.00', '0.00'], [$cargo->descuento, $cargo->recargo, $cargo->impuestos]);
        $this->assertSame([2027, 1, 'Libro'], [$cargo->periodo_anio, $cargo->periodo_mes, $cargo->observaciones]);
        $this->assertSame([Cargo::ESTADO_PENDIENTE, Cargo::ORIGEN_MANUAL, 'MXN', null, $user->getKey()], [$cargo->estado, $cargo->origen, $cargo->moneda, $cargo->clave_idempotencia, $cargo->created_by]);
        $this->assertIsString($cargo->subtotal);
    }

    public function test_optional_period_and_author_are_null_and_past_dates_do_not_mark_overdue(): void
    {
        $cargo = $this->service->crear($this->valid(['fecha_emision' => '2020-01-01', 'fecha_vencimiento' => '2020-01-01']), null)->fresh();
        $this->assertNull($cargo->periodo_anio); $this->assertNull($cargo->periodo_mes); $this->assertNull($cargo->created_by);
        $this->assertSame(Cargo::ESTADO_PENDIENTE, $cargo->estado);
    }

    /** @dataProvider invalidAmounts */
    public function test_rejects_invalid_amounts_without_partial_records($amount): void
    {
        $this->expectException(CargoManualInvalidoException::class);
        try { $this->service->crear($this->valid(['subtotal' => $amount]), null); }
        finally { $this->assertDatabaseCount('cargos', 0); }
    }

    public static function invalidAmounts(): array
    {
        return [['0'], ['-1'], ['1.001'], ['10000000000.00'], ['1e3'], ['1,000.00'], ['NaN'], ['INF'], ['1 0'], [1.2]];
    }

    public function test_accepts_decimal_maximum_without_float_conversion(): void
    {
        $this->assertSame('9999999999.99', $this->service->crear($this->valid(['subtotal' => '9999999999.99']), null)->fresh()->total);
    }

    /** @dataProvider reservedConcepts */
    public function test_rejects_every_reserved_concept_by_key(string $key): void
    {
        $this->concepto->update(['clave' => 'PERMITIDO']);
        $reserved = ConceptoCobro::where('clave', $key)->first() ?: ConceptoCobro::create(['clave' => $key, 'nombre' => $key, 'activo' => true]);
        $this->expectException(CargoManualInvalidoException::class);
        $this->service->crear($this->valid(['concepto_cobro_id' => $reserved->getKey()]), null);
    }

    public static function reservedConcepts(): array { return [['INSCRIPCION'], ['MENSUALIDAD'], ['RECARGO'], ['DESCUENTO']]; }

    public function test_rejects_missing_inactive_concepts_and_missing_or_deleted_enrollments(): void
    {
        foreach ([999999, tap($this->concepto, fn ($item) => $item->update(['activo' => false]))->getKey()] as $id) {
            try { $this->service->crear($this->valid(['concepto_cobro_id' => $id]), null); $this->fail('Expected concept rejection.'); }
            catch (CargoManualInvalidoException $exception) { $this->assertSame('concepto_cobro_id', $exception->campo()); }
        }
        $this->concepto->update(['activo' => true]);
        foreach ([999999, tap($this->inscripcion, fn ($item) => $item->delete())->getKey()] as $id) {
            try { $this->service->crear($this->valid(['inscripciones_id' => $id]), null); $this->fail('Expected enrollment rejection.'); }
            catch (CargoManualInvalidoException $exception) { $this->assertSame('inscripciones_id', $exception->campo()); }
        }
        $this->assertDatabaseCount('cargos', 0);
    }

    /** @dataProvider invalidPeriodsAndDates */
    public function test_rejects_invalid_periods_and_dates(array $changes): void
    {
        $this->expectException(CargoManualInvalidoException::class);
        $this->service->crear($this->valid($changes), null);
    }

    public static function invalidPeriodsAndDates(): array
    {
        return [[['periodo_anio' => 2026]], [['periodo_mes' => 1]], [['periodo_anio' => 2026, 'periodo_mes' => 0]], [['periodo_anio' => 2026, 'periodo_mes' => 13]], [['fecha_vencimiento' => '2025-12-31']], [['fecha_emision' => null]], [['fecha_vencimiento' => null]]];
    }

    private function valid(array $changes = []): array
    {
        return array_merge(['inscripciones_id' => $this->inscripcion->getKey(), 'concepto_cobro_id' => $this->concepto->getKey(), 'subtotal' => '100.00', 'fecha_emision' => '2026-01-01', 'fecha_vencimiento' => '2026-01-02', 'periodo_anio' => null, 'periodo_mes' => null, 'observaciones' => null], $changes);
    }
}
