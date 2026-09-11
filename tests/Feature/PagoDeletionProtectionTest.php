<?php

namespace Tests\Feature;

use App\Models\Pago;
use LogicException;

class PagoDeletionProtectionTest extends PagosTestCase
{
    /** @dataProvider protectedStates */
    public function test_financial_payment_cannot_be_deleted_with_eloquent(string $state, string $method): void
    {
        $pago = Pago::create($this->paymentAttributes());
        $pago->forceFill(['estado' => $state])->save();

        try {
            $pago->{$method}();
            $this->fail('El pago financiero no debe eliminarse.');
        } catch (LogicException $exception) {
            $this->assertDatabaseHas('pagos', ['pago_id' => $pago->getKey(), 'estado' => $state]);
        }
    }

    public function protectedStates(): array
    {
        return [
            [Pago::ESTADO_CONFIRMADO, 'delete'],
            [Pago::ESTADO_CONFIRMADO, 'deleteOrFail'],
            [Pago::ESTADO_CANCELADO, 'delete'],
            [Pago::ESTADO_CANCELADO, 'deleteOrFail'],
            [Pago::ESTADO_REEMBOLSADO, 'delete'],
            [Pago::ESTADO_REEMBOLSADO, 'deleteOrFail'],
        ];
    }

    public function test_true_draft_can_still_be_deleted(): void
    {
        $pago = Pago::create($this->paymentAttributes());

        $this->assertTrue($pago->delete());
        $this->assertDatabaseMissing('pagos', ['pago_id' => $pago->getKey()]);
    }
}
