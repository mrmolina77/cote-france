<?php

namespace Tests\Feature;

use App\Exceptions\InscripcionFinancieraInvalidaException;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Inscripcion;
use App\Models\ResponsablePago;
use App\Services\Facturacion\GeneradorCargosService;

class GeneradorCargosServiceTest extends InscripcionesTestCase
{
    private GeneradorCargosService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeneradorCargosService();
    }

    public function test_generates_enrollment_charge_and_monthly_calendar_without_fixed_concept_ids(): void
    {
        ConceptoCobro::where('clave', 'INSCRIPCION')->update(['concepto_cobro_id' => 41]);
        ConceptoCobro::where('clave', 'MENSUALIDAD')->update(['concepto_cobro_id' => 73]);
        $inscripcion = $this->financialEnrollment([
            'fecha_inscripcion' => '2026-09-07', 'fecha_inicio' => '2026-09-10',
            'numero_mensualidades' => 3, 'dia_vencimiento' => 15,
        ]);

        $cargos = $this->service->generarParaInscripcion($inscripcion, 99);

        $this->assertCount(4, $cargos);
        $cargo = $cargos->first();
        $this->assertSame(41, $cargo->concepto_cobro_id);
        $this->assertSame('2026-09-07', $cargo->fecha_emision->format('Y-m-d'));
        $this->assertSame('2026-09-10', $cargo->fecha_vencimiento->format('Y-m-d'));
        $this->assertSame('INSCRIPCION:'.$inscripcion->getKey(), $cargo->clave_idempotencia);
        $this->assertNull($cargo->periodo_anio);
        $this->assertNull($cargo->periodo_mes);
        $this->assertSame(99, $cargo->created_by);

        $mensualidades = $cargos->slice(1)->values();
        $this->assertSame(['2026-09', '2026-10', '2026-11'], $mensualidades->map(fn ($item) => sprintf('%04d-%02d', $item->periodo_anio, $item->periodo_mes))->all());
        $this->assertSame(['2026-09-01', '2026-10-01', '2026-11-01'], $mensualidades->map(fn ($item) => $item->fecha_emision->format('Y-m-d'))->all());
        $this->assertSame(['2026-09-15', '2026-10-15', '2026-11-15'], $mensualidades->map(fn ($item) => $item->fecha_vencimiento->format('Y-m-d'))->all());
        $this->assertSame('MENSUALIDAD:'.$inscripcion->getKey().':2026-09', $mensualidades[0]->clave_idempotencia);
    }

    public function test_zero_or_null_amounts_generate_nothing_and_require_no_concepts(): void
    {
        ConceptoCobro::query()->delete();
        foreach ([null, '0.00'] as $amount) {
            $inscripcion = $this->financialEnrollment([
                'monto_inscripcion' => $amount, 'monto_mensualidad' => $amount,
                'dia_vencimiento' => null, 'numero_mensualidades' => null,
            ]);
            $this->assertCount(0, $this->service->generarParaInscripcion($inscripcion));
        }
    }

    public function test_zero_amounts_generate_nothing_without_concepts(): void
    {
        ConceptoCobro::query()->delete();
        $inscripcion = $this->financialEnrollment([
            'monto_inscripcion' => '0.00', 'monto_mensualidad' => '0.00',
            'dia_vencimiento' => null, 'numero_mensualidades' => null,
        ]);

        $this->assertTrue($this->service->generarParaInscripcion($inscripcion)->isEmpty());
        $this->assertDatabaseCount('cargos', 0);
    }

    public function test_due_day_is_clamped_and_end_date_is_inclusive(): void
    {
        $inscripcion = $this->financialEnrollment([
            'fecha_inicio' => '2027-01-10', 'fecha_fin' => '2027-04-02',
            'numero_mensualidades' => 12, 'dia_vencimiento' => 31,
        ]);

        $dates = $this->service->generarMensualidades($inscripcion)
            ->pluck('fecha_vencimiento')->map->format('Y-m-d')->all();
        $this->assertSame(['2027-01-31', '2027-02-28', '2027-03-31', '2027-04-30'], $dates);

        $leap = $this->financialEnrollment([
            'fecha_inicio' => '2028-02-10', 'numero_mensualidades' => 1, 'dia_vencimiento' => 31,
        ]);
        $this->assertSame('2028-02-29', $this->service->generarMensualidades($leap)->first()->fecha_vencimiento->format('Y-m-d'));
    }

    public function test_generation_is_idempotent_and_does_not_restore_existing_charge(): void
    {
        $inscripcion = $this->financialEnrollment(['numero_mensualidades' => 2]);
        $first = $this->service->generarParaInscripcion($inscripcion);
        $monthly = $first->last();
        $monthly->update(['estado' => Cargo::ESTADO_PARCIAL, 'saldo_pendiente' => '25.00', 'observaciones' => 'Con pago']);

        $second = $this->service->generarParaInscripcion($inscripcion, 123);

        $this->assertCount(3, $second);
        $this->assertDatabaseCount('cargos', 3);
        $unchanged = $monthly->fresh();
        $this->assertSame(Cargo::ESTADO_PARCIAL, $unchanged->estado);
        $this->assertSame('25.00', $unchanged->saldo_pendiente);
        $this->assertSame('Con pago', $unchanged->observaciones);
        $this->assertNull($unchanged->created_by);
    }

    public function test_amounts_initial_state_origin_and_author_are_exact(): void
    {
        $inscripcion = $this->financialEnrollment(['monto_inscripcion' => '1234.56', 'numero_mensualidades' => 1]);
        $cargos = $this->service->generarParaInscripcion($inscripcion);

        foreach ($cargos as $cargo) {
            $this->assertIsString($cargo->fresh()->total);
            $this->assertSame($cargo->subtotal, $cargo->total);
            $this->assertSame($cargo->total, $cargo->saldo_pendiente);
            $this->assertSame('0.00', $cargo->descuento);
            $this->assertSame('0.00', $cargo->recargo);
            $this->assertSame('0.00', $cargo->impuestos);
            $this->assertSame(Cargo::ESTADO_PENDIENTE, $cargo->estado);
            $this->assertSame(Cargo::ORIGEN_AUTOMATICO, $cargo->origen);
            $this->assertNull($cargo->created_by);
        }
    }

    public function test_invalid_enrollment_responsible_or_required_concept_creates_no_partial_charges(): void
    {
        $inscripcion = $this->financialEnrollment();
        ConceptoCobro::where('clave', 'MENSUALIDAD')->update(['activo' => false]);

        try {
            $this->service->generarParaInscripcion($inscripcion);
            $this->fail('Expected domain exception.');
        } catch (InscripcionFinancieraInvalidaException $exception) {
            $this->assertStringContainsString('MENSUALIDAD', $exception->getMessage());
        }
        $this->assertDatabaseCount('cargos', 0);

        $inscripcion->responsablePago->update(['activo' => false]);
        $this->expectException(InscripcionFinancieraInvalidaException::class);
        $this->service->generarCargoInscripcion($inscripcion);
    }

    public function test_soft_deleted_enrollment_cannot_generate_charges(): void
    {
        $inscripcion = $this->financialEnrollment();
        $inscripcion->delete();

        $this->expectException(InscripcionFinancieraInvalidaException::class);
        $this->service->generarParaInscripcion($inscripcion);
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
