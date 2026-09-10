<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\PagoAplicacion;
use App\Models\ResponsablePago;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PagoAplicacionModelTest extends InscripcionesTestCase
{
    public function test_configuration_casts_relations_and_mass_assignment_protection(): void
    {
        $model = new PagoAplicacion();
        $this->assertSame('pago_aplicaciones', $model->getTable());
        $this->assertSame('pago_aplicacion_id', $model->getKeyName());
        foreach (['importe_aplicado', 'saldo_anterior', 'saldo_posterior'] as $field) {
            $this->assertSame('decimal:2', $model->getCasts()[$field]);
        }
        $this->assertInstanceOf(BelongsTo::class, $model->pago());
        $this->assertInstanceOf(BelongsTo::class, $model->cargo());
        $this->assertInstanceOf(HasMany::class, (new Pago())->aplicaciones());
        $this->assertInstanceOf(HasMany::class, (new Cargo())->aplicacionesPago());
        $this->assertSame(['*'], $model->getGuarded());
    }

    public function test_historical_balances_are_persisted_exactly(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $responsable = ResponsablePago::create([
            'tipo' => 'persona', 'prospectos_id' => $prospecto->getKey(),
            'nombre_razon_social' => 'Responsable', 'activo' => true,
        ]);
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $inscripcion->update(['responsable_pago_id' => $responsable->getKey()]);
        $cargo = Cargo::create([
            'inscripciones_id' => $inscripcion->getKey(), 'concepto_cobro_id' => 1,
            'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-30',
            'moneda' => 'MXN', 'subtotal' => '0.30', 'total' => '0.30', 'saldo_pendiente' => '0.30',
            'estado' => Cargo::ESTADO_PENDIENTE, 'origen' => Cargo::ORIGEN_MANUAL,
        ]);
        $metodo = MetodoPago::query()->firstOrFail();
        $pago = Pago::forceCreate([
            'folio' => 'PAG-2026-000001', 'inscripciones_id' => $inscripcion->getKey(),
            'prospectos_id' => $prospecto->getKey(), 'responsable_pago_id' => $responsable->getKey(),
            'fecha_pago' => now(), 'zona_horaria' => 'UTC', 'moneda' => 'MXN',
            'monto' => '0.10', 'metodo_pago_id' => $metodo->getKey(),
        ]);

        $created = PagoAplicacion::forceCreate([
            'pago_id' => $pago->getKey(), 'cargo_id' => $cargo->getKey(),
            'importe_aplicado' => '0.10', 'saldo_anterior' => '0.30', 'saldo_posterior' => '0.20',
        ]);
        $persisted = PagoAplicacion::query()->findOrFail($created->getKey());

        $this->assertSame('0.10', $persisted->importe_aplicado);
        $this->assertSame('0.30', $persisted->saldo_anterior);
        $this->assertSame('0.20', $persisted->saldo_posterior);
        $this->assertIsString($persisted->importe_aplicado);
        $this->assertIsString($persisted->saldo_anterior);
        $this->assertIsString($persisted->saldo_posterior);
    }
}
