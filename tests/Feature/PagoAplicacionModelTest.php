<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Pago;
use App\Models\PagoAplicacion;
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
        $model = new PagoAplicacion();
        $model->forceFill(['importe_aplicado' => '0.10', 'saldo_anterior' => '0.30', 'saldo_posterior' => '0.20']);
        $this->assertSame('0.10', $model->importe_aplicado);
        $this->assertSame('0.30', $model->saldo_anterior);
        $this->assertSame('0.20', $model->saldo_posterior);
    }
}
