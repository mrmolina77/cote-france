<?php

namespace Tests\Feature;

use App\Models\Inscripcion;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Prospecto;
use App\Models\ResponsablePago;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PagoModelTest extends PagosTestCase
{
    public function test_configuration_constants_and_casts(): void
    {
        $pago = new Pago();
        $this->assertSame('pagos', $pago->getTable());
        $this->assertSame('pago_id', $pago->getKeyName());
        $this->assertSame(['borrador', 'confirmado', 'cancelado', 'reembolsado'], Pago::ESTADOS);
        $this->assertSame('decimal:2', $pago->getCasts()['monto']);
        $this->assertSame('decimal:6', $pago->getCasts()['tipo_cambio']);
        foreach (['fecha_pago', 'fecha_movimiento', 'fecha_confirmacion', 'fecha_cancelacion', 'fecha_reembolso'] as $date) {
            $this->assertSame('datetime', $pago->getCasts()[$date]);
        }
    }

    public function test_direct_inverse_and_self_referencing_relationships(): void
    {
        $pago = new Pago();
        foreach (['inscripcion', 'prospecto', 'responsablePago', 'metodoPago', 'createdBy', 'confirmedBy', 'cancelledBy', 'anticipoRelacionado'] as $relation) {
            $this->assertInstanceOf(BelongsTo::class, $pago->{$relation}());
        }
        $this->assertInstanceOf(HasMany::class, $pago->pagosRelacionadosComoAnticipo());
        foreach ([[new Inscripcion(), 'pagos'], [new Prospecto(), 'pagos'], [new ResponsablePago(), 'pagos'], [new MetodoPago(), 'pagos']] as [$model, $relation]) {
            $this->assertInstanceOf(HasMany::class, $model->{$relation}());
        }
        $this->assertSame(Inscripcion::class, $pago->inscripcion()->getRelated()::class);
        $this->assertSame(Prospecto::class, $pago->prospecto()->getRelated()::class);
        $this->assertSame(User::class, $pago->createdBy()->getRelated()::class);
    }

    public function test_scopes_filter_without_modifying_records(): void
    {
        foreach (Pago::ESTADOS as $index => $estado) {
            $pago = Pago::create($this->paymentAttributes([
                'folio' => sprintf('PAG-2026-%06d', $index + 1),
            ]));

            $pago->forceFill(['estado' => $estado])->save();
        }
        $target = Pago::create($this->paymentAttributes(['folio' => 'PAG-2026-000010']));
        $estadosAntesDeScopes = Pago::orderBy('pago_id')->pluck('estado', 'pago_id')->all();

        $this->assertSame(2, Pago::borradores()->count());
        $this->assertSame(1, Pago::confirmados()->count());
        $this->assertSame(1, Pago::cancelados()->count());
        $this->assertSame(1, Pago::reembolsados()->count());
        $this->assertSame(1, Pago::delAlumno($target->prospectos_id)->count());
        $this->assertSame(1, Pago::deLaInscripcion($target->inscripciones_id)->count());
        $this->assertSame(Pago::ESTADO_BORRADOR, $target->fresh()->estado);
        $this->assertSame(
            $estadosAntesDeScopes,
            Pago::orderBy('pago_id')->pluck('estado', 'pago_id')->all()
        );
    }

    public function test_decimal_casts_are_strings_and_sat_form_is_historical(): void
    {
        $pago = Pago::create($this->paymentAttributes(['forma_pago_sat' => '31', 'monto' => '98.10', 'tipo_cambio' => '1.234567']))->fresh();
        $this->assertSame('98.10', $pago->monto);
        $this->assertSame('1.234567', $pago->tipo_cambio);
        $this->assertIsString($pago->monto);
        $this->assertIsString($pago->tipo_cambio);
        $this->assertSame('31', $pago->forma_pago_sat);
        MetodoPago::whereKey($pago->metodo_pago_id)->update(['nombre' => 'Configuración modificada']);
        $this->assertSame('31', $pago->fresh()->forma_pago_sat);
    }
}
