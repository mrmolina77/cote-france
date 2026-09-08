<?php

namespace Tests\Feature;

use App\Exceptions\InscripcionFinancieraInvalidaException;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Inscripcion;
use App\Models\ResponsablePago;
use App\Services\Facturacion\GeneradorCargosService;
use Illuminate\Database\QueryException;

class GeneradorCargosServiceTest extends InscripcionesTestCase
{
    private GeneradorCargosService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeneradorCargosService();
    }

    public function test_generar_cargo_inscripcion_is_independently_idempotent_and_returns_existing_charge(): void
    {
        $firstUser = $this->user('admin');
        $secondUser = $this->user('venta');
        $inscripcion = $this->financialEnrollment();
        $first = $this->service->generarCargoInscripcion($inscripcion, $firstUser->getKey());
        $first->update([
            'estado' => Cargo::ESTADO_PARCIAL, 'saldo_pendiente' => '25.00', 'total' => '450.00',
            'fecha_vencimiento' => '2030-12-20', 'observaciones' => 'Con pago',
        ]);

        $second = $this->service->generarCargoInscripcion($inscripcion, $secondUser->getKey());

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('cargos', 1);
        $this->assertSame([
            Cargo::ESTADO_PARCIAL, '25.00', '450.00', '2030-12-20', 'Con pago', $firstUser->getKey(),
        ], $this->protectedValues($second));
    }

    public function test_generar_mensualidades_is_independently_idempotent_and_returns_existing_charges(): void
    {
        $firstUser = $this->user('admin');
        $secondUser = $this->user('venta');
        $inscripcion = $this->financialEnrollment(['numero_mensualidades' => 2]);
        $first = $this->service->generarMensualidades($inscripcion, $firstUser->getKey());
        $first[0]->update([
            'estado' => Cargo::ESTADO_PARCIAL, 'saldo_pendiente' => '10.00', 'total' => '900.00',
            'fecha_vencimiento' => '2031-01-20', 'observaciones' => 'Abono mensual',
        ]);

        $second = $this->service->generarMensualidades($inscripcion, $secondUser->getKey());

        $this->assertSame($first->pluck('cargo_id')->all(), $second->pluck('cargo_id')->all());
        $this->assertDatabaseCount('cargos', 2);
        $this->assertSame([
            Cargo::ESTADO_PARCIAL, '10.00', '900.00', '2031-01-20', 'Abono mensual', $firstUser->getKey(),
        ], $this->protectedValues($second[0]));
    }

    public function test_generar_para_inscripcion_is_independently_idempotent(): void
    {
        $inscripcion = $this->financialEnrollment(['numero_mensualidades' => 2]);
        $first = $this->service->generarParaInscripcion($inscripcion);
        $second = $this->service->generarParaInscripcion($inscripcion);

        $this->assertSame($first->pluck('cargo_id')->all(), $second->pluck('cargo_id')->all());
        $this->assertDatabaseCount('cargos', 3);
    }

    /** @dataProvider emptyAmounts */
    public function test_enrollment_charge_returns_null_for_empty_amount($amount): void
    {
        $inscripcion = $this->financialEnrollment(['monto_inscripcion' => $amount]);
        $this->assertNull($this->service->generarCargoInscripcion($inscripcion));
        $this->assertDatabaseCount('cargos', 0);
    }

    /** @dataProvider emptyAmounts */
    public function test_monthly_generation_returns_empty_collection_for_empty_amount($amount): void
    {
        $inscripcion = $this->financialEnrollment([
            'monto_mensualidad' => $amount, 'dia_vencimiento' => null, 'numero_mensualidades' => null,
        ]);
        $this->assertTrue($this->service->generarMensualidades($inscripcion)->isEmpty());
        $this->assertDatabaseCount('cargos', 0);
    }

    public function emptyAmounts(): array
    {
        return ['null' => [null], 'zero' => ['0.00']];
    }

    /** @dataProvider unavailableConcepts */
    public function test_each_required_concept_must_exist_and_be_active(string $key, bool $delete): void
    {
        $inscripcion = $this->financialEnrollment();
        $concept = ConceptoCobro::where('clave', $key)->firstOrFail();
        $delete ? $concept->delete() : $concept->update(['activo' => false]);

        try {
            $this->service->generarParaInscripcion($inscripcion);
            $this->fail('Expected domain exception.');
        } catch (InscripcionFinancieraInvalidaException $exception) {
            $this->assertStringContainsString($key, $exception->getMessage());
        }
        $this->assertDatabaseCount('cargos', 0);
    }

    public function unavailableConcepts(): array
    {
        return [
            'missing enrollment' => ['INSCRIPCION', true], 'inactive enrollment' => ['INSCRIPCION', false],
            'missing monthly' => ['MENSUALIDAD', true], 'inactive monthly' => ['MENSUALIDAD', false],
        ];
    }

    public function test_zero_amount_does_not_require_its_concept_and_concepts_are_found_by_key(): void
    {
        ConceptoCobro::where('clave', 'INSCRIPCION')->update(['concepto_cobro_id' => 41]);
        ConceptoCobro::where('clave', 'MENSUALIDAD')->update(['concepto_cobro_id' => 73]);
        $inscripcion = $this->financialEnrollment(['monto_inscripcion' => '0.00', 'numero_mensualidades' => 1]);
        $charges = $this->service->generarParaInscripcion($inscripcion);

        $this->assertCount(1, $charges);
        $this->assertSame(73, $charges->first()->concepto_cobro_id);

        Cargo::query()->delete();
        ConceptoCobro::where('clave', 'MENSUALIDAD')->delete();
        $inscripcion->update(['monto_inscripcion' => '50.00', 'monto_mensualidad' => '0.00', 'dia_vencimiento' => null, 'numero_mensualidades' => null]);
        $this->assertSame(41, $this->service->generarParaInscripcion($inscripcion)->first()->concepto_cobro_id);
    }

    /** @dataProvider invalidFinancialAttributes */
    public function test_invalid_financial_configuration_creates_no_charges_and_does_not_touch_other_enrollment(array $attributes): void
    {
        $other = $this->financialEnrollment(['numero_mensualidades' => 1]);
        $otherCharge = $this->service->generarCargoInscripcion($other);
        $invalid = $this->financialEnrollment($attributes);

        try {
            $this->service->generarParaInscripcion($invalid);
            $this->fail('Expected invalid financial configuration.');
        } catch (InscripcionFinancieraInvalidaException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }

        $this->assertSame(1, Cargo::where('inscripciones_id', $other->getKey())->count());
        $this->assertTrue($otherCharge->is($otherCharge->fresh()));
        $this->assertSame(0, Cargo::where('inscripciones_id', $invalid->getKey())->count());
    }

    public function invalidFinancialAttributes(): array
    {
        return [
            'currency' => [['moneda' => 'USD']], 'missing start' => [['fecha_inicio' => null]],
            'end before start' => [['fecha_fin' => '2026-09-09']], 'negative discount' => [['descuento' => '-1.00']],
            'negative scholarship' => [['beca' => '-1.00']], 'discount over 100' => [['descuento' => '100.01']],
            'scholarship over 100' => [['beca' => '100.01']], 'combined over 100' => [['descuento' => '60', 'beca' => '41']],
            'missing monthly day' => [['dia_vencimiento' => null]], 'missing installments' => [['numero_mensualidades' => null]],
            'day zero' => [['dia_vencimiento' => 0]], 'day over 31' => [['dia_vencimiento' => 32]],
            'installments zero' => [['numero_mensualidades' => 0]], 'installments over 120' => [['numero_mensualidades' => 121]],
        ];
    }

    public function test_unpersisted_soft_deleted_and_invalid_responsible_enrollments_are_rejected(): void
    {
        $unpersisted = new Inscripcion();
        foreach ([$unpersisted, $this->deletedEnrollment(), $this->enrollmentWithInactiveResponsible(), $this->enrollmentWithoutResponsible()] as $inscripcion) {
            try {
                $this->service->generarParaInscripcion($inscripcion);
                $this->fail('Expected domain exception.');
            } catch (InscripcionFinancieraInvalidaException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
        $this->assertDatabaseCount('cargos', 0);
    }

    public function test_calendar_crosses_year_clamps_dates_and_obeys_inclusive_end(): void
    {
        $inscripcion = $this->financialEnrollment([
            'fecha_inicio' => '2027-12-10', 'fecha_fin' => '2028-04-02', 'numero_mensualidades' => 12, 'dia_vencimiento' => 31,
        ]);
        $items = $this->service->generarMensualidades($inscripcion);

        $this->assertSame(['2027-12', '2028-01', '2028-02', '2028-03', '2028-04'], $items->map(fn ($c) => sprintf('%04d-%02d', $c->periodo_anio, $c->periodo_mes))->all());
        $this->assertSame(['2027-12-01', '2028-01-01', '2028-02-01', '2028-03-01', '2028-04-01'], $items->map(fn ($c) => $c->fecha_emision->format('Y-m-d'))->all());
        $this->assertSame(['2027-12-31', '2028-01-31', '2028-02-29', '2028-03-31', '2028-04-30'], $items->map(fn ($c) => $c->fecha_vencimiento->format('Y-m-d'))->all());

        $short = $this->financialEnrollment(['fecha_inicio' => '2027-01-10', 'fecha_fin' => '2027-12-20', 'numero_mensualidades' => 2]);
        $this->assertCount(2, $this->service->generarMensualidades($short));
        $nonLeap = $this->financialEnrollment(['fecha_inicio' => '2027-02-10', 'numero_mensualidades' => 1, 'dia_vencimiento' => 31]);
        $this->assertSame('2027-02-28', $this->service->generarMensualidades($nonLeap)->first()->fecha_vencimiento->format('Y-m-d'));
    }

    public function test_generation_does_not_modify_enrollment_and_amounts_are_exact_decimals_without_applying_discounts(): void
    {
        $inscripcion = $this->financialEnrollment([
            'fecha_inicio' => '2020-01-10', 'monto_inscripcion' => '1234.56', 'monto_mensualidad' => '987.65',
            'numero_mensualidades' => 1, 'descuento' => '20.00', 'beca' => '30.00',
        ]);
        $original = $inscripcion->getRawOriginal();
        $charges = $this->service->generarParaInscripcion($inscripcion);

        $this->assertSame($original, $inscripcion->fresh()->getRawOriginal());
        foreach ($charges as $charge) {
            $fresh = $charge->fresh();
            $expected = $fresh->periodo_mes === null ? '1234.56' : '987.65';
            $this->assertSame([$expected, $expected, $expected], [$fresh->subtotal, $fresh->total, $fresh->saldo_pendiente]);
            $this->assertSame(['0.00', '0.00', '0.00'], [$fresh->descuento, $fresh->recargo, $fresh->impuestos]);
            $this->assertIsString($fresh->total);
            $this->assertSame(Cargo::ESTADO_PENDIENTE, $fresh->estado);
            $this->assertSame(Cargo::ORIGEN_AUTOMATICO, $fresh->origen);
        }
        $this->assertStringNotContainsString('float', strtolower(json_encode((new Cargo())->getCasts())));
        $this->assertStringNotContainsString('double', strtolower(json_encode((new Cargo())->getCasts())));
        $this->assertStringNotContainsString('real', strtolower(json_encode((new Cargo())->getCasts())));
    }

    public function test_relations_are_available_and_generation_is_isolated(): void
    {
        $target = $this->financialEnrollment(['numero_mensualidades' => 1]);
        $other = $this->financialEnrollment(['numero_mensualidades' => 1]);
        $otherCharge = $this->service->generarCargoInscripcion($other);
        $charges = $this->service->generarParaInscripcion($target);
        $charge = $charges->first();

        $this->assertTrue($charge->inscripcion->is($target));
        $this->assertTrue($charge->conceptoCobro->cargos->contains($charge));
        $this->assertTrue($charge->inscripcion->prospecto->is($target->prospecto));
        $this->assertCount(2, $target->cargos);
        $this->assertTrue($otherCharge->is($otherCharge->fresh()));
    }

    public function test_unique_idempotency_constraint_rejects_duplicate_and_error_classifier_is_specific(): void
    {
        $inscripcion = $this->financialEnrollment();
        $charge = $this->service->generarCargoInscripcion($inscripcion);
        $duplicate = $charge->replicate();

        try {
            $duplicate->save();
            $this->fail('Expected unique constraint violation.');
        } catch (QueryException $exception) {
            $this->assertTrue($this->classifiesAsIdempotencyConflict($exception));
        }
        $unrelated = new QueryException('insert into cargos', [], new \PDOException('UNIQUE constraint failed: cargos.cargo_id', 23000));
        $this->assertFalse($this->classifiesAsIdempotencyConflict($unrelated));
        $this->assertDatabaseCount('cargos', 1);
        $this->assertSame($charge->total, $charge->fresh()->total);
    }

    public function test_complete_generation_rolls_back_enrollment_charge_if_monthly_concept_fails(): void
    {
        $other = $this->financialEnrollment(['numero_mensualidades' => 1]);
        $existing = $this->service->generarCargoInscripcion($other);
        $target = $this->financialEnrollment();
        ConceptoCobro::where('clave', 'MENSUALIDAD')->update(['activo' => false]);

        try {
            $this->service->generarParaInscripcion($target);
            $this->fail('Expected monthly generation failure.');
        } catch (InscripcionFinancieraInvalidaException $exception) {
            $this->assertStringContainsString('MENSUALIDAD', $exception->getMessage());
        }
        $this->assertSame(0, Cargo::where('inscripciones_id', $target->getKey())->count());
        $this->assertTrue($existing->is($existing->fresh()));
        $this->assertDatabaseCount('cargos', 1);
    }

    private function protectedValues(Cargo $charge): array
    {
        $charge = $charge->fresh();
        return [$charge->estado, $charge->saldo_pendiente, $charge->total, $charge->fecha_vencimiento->format('Y-m-d'), $charge->observaciones, $charge->created_by];
    }

    private function classifiesAsIdempotencyConflict(QueryException $exception): bool
    {
        $method = new \ReflectionMethod($this->service, 'esConflictoDeClaveIdempotencia');
        $method->setAccessible(true);
        return $method->invoke($this->service, $exception);
    }

    private function deletedEnrollment(): Inscripcion
    {
        $inscripcion = $this->financialEnrollment();
        $inscripcion->delete();
        return $inscripcion;
    }

    private function enrollmentWithInactiveResponsible(): Inscripcion
    {
        $inscripcion = $this->financialEnrollment();
        $inscripcion->responsablePago->update(['activo' => false]);
        return $inscripcion;
    }

    private function enrollmentWithoutResponsible(): Inscripcion
    {
        return $this->financialEnrollment(['responsable_pago_id' => 999999]);
    }

    private function financialEnrollment(array $overrides = []): Inscripcion
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $responsable = ResponsablePago::activeForProspect($prospecto);

        return Inscripcion::create(array_merge([
            'fecha_inscripcion' => '2026-09-07', 'prospectos_id' => $prospecto->getKey(),
            'cursos_id' => $curso->getKey(), 'grupo_id' => $grupo->getKey(), 'estatus' => 'activa',
            'fecha_inicio' => '2026-09-10', 'fecha_fin' => null, 'moneda' => 'MXN',
            'monto_inscripcion' => '500.00', 'monto_mensualidad' => '1000.00',
            'dia_vencimiento' => 15, 'numero_mensualidades' => 3,
            'descuento' => '0.00', 'beca' => '0.00', 'responsable_pago_id' => $responsable->getKey(),
        ], $overrides));
    }
}
