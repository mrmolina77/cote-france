<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowPagos;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\ResponsablePago;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

class ShowPagosTest extends InscripcionesTestCase
{
    public function test_access_and_cancellation_actions_are_restricted_to_admins(): void
    {
        $this->get('/facturacion/pagos')->assertRedirect('/login');
        $admin = $this->user('admin');
        $this->actingAs($admin)->get('/facturacion/pagos')->assertOk();
        $this->assertTrue(Gate::forUser($admin)->allows('manage-pagos'));
        $this->assertTrue(Gate::forUser($admin)->allows('cancel-pagos'));

        foreach (['venta', 'profe'] as $rol) {
            $usuario = $this->user($rol);
            $this->actingAs($usuario)->get('/facturacion/pagos')->assertForbidden();
            Livewire::actingAs($usuario)->test(ShowPagos::class)->assertForbidden();
        }

        $sinRol = User::factory()->create(['roles_id' => 999999]);
        $this->actingAs($sinRol)->get('/facturacion/pagos')->assertForbidden();
    }

    public function test_lists_searches_filters_and_sorts_payments(): void
    {
        $admin = $this->user('admin');
        $anterior = $this->pago($admin, ['folio' => 'PAG-ANTERIOR', 'fecha_pago' => '2026-09-01 10:00:00']);
        $reciente = $this->pago($admin, ['folio' => 'PAG-BUSCADO', 'referencia' => 'REF-UNICA', 'fecha_pago' => '2026-09-10 10:00:00']);

        Livewire::actingAs($admin)->test(ShowPagos::class)
            ->assertSee('PAG-ANTERIOR')->assertSee('PAG-BUSCADO')
            ->assertSeeInOrder([$reciente->folio, $anterior->folio])
            ->set('busqueda', 'REF-UNICA')->assertSee('PAG-BUSCADO')->assertDontSee('PAG-ANTERIOR')
            ->set('busqueda', "%' OR 1=1 --")->assertDontSee('PAG-ANTERIOR')
            ->set('busqueda', '')->set('estado', Pago::ESTADO_CONFIRMADO)->assertSee('PAG-BUSCADO')
            ->set('porPagina', 999)->assertSet('porPagina', 10)
            ->set('estado', "confirmado' OR 1=1 --")->assertHasNoErrors();
    }

    /** @dataProvider fechasInvalidas */
    public function test_rejects_impossible_dates_without_changing_payments(string $campo, string $fecha): void
    {
        $admin = $this->user('admin');
        $pago = $this->pago($admin);

        Livewire::actingAs($admin)->test(ShowPagos::class)->set($campo, $fecha)->assertHasErrors($campo);

        $this->assertSame(Pago::ESTADO_CONFIRMADO, $pago->fresh()->estado);
        $this->assertDatabaseCount('pagos', 1);
    }

    public function fechasInvalidas(): array
    {
        return [
            'febrero 30' => ['fechaDesde', '2026-02-30'],
            'mes 13' => ['fechaHasta', '2026-13-01'],
            'día inexistente' => ['fechaDesde', '2026-04-31'],
            'texto' => ['fechaHasta', 'mañana'],
        ];
    }

    public function test_accepts_real_dates_empty_dates_and_rejects_an_inverted_range(): void
    {
        $admin = $this->user('admin');
        $componente = Livewire::actingAs($admin)->test(ShowPagos::class)
            ->set('fechaDesde', '2026-02-28')->assertHasNoErrors('fechaDesde')
            ->set('fechaHasta', '2026-09-10')->assertHasNoErrors(['fechaDesde', 'fechaHasta'])
            ->set('fechaDesde', '2026-09-11')->assertHasErrors('fechaHasta')
            ->set('fechaDesde', '')->set('fechaHasta', '')->assertHasNoErrors(['fechaDesde', 'fechaHasta']);

        $componente->set('fechaHasta', '2026-09-10')->assertHasNoErrors('fechaHasta');
    }

    public function test_modal_rejects_non_confirmed_and_invalid_payment_ids(): void
    {
        $admin = $this->user('admin');
        $borrador = $this->pago($admin, ['estado' => Pago::ESTADO_BORRADOR]);
        $componente = Livewire::actingAs($admin)->test(ShowPagos::class);

        $componente->call('prepararCancelacion', $borrador->getKey())->assertHasErrors('pagoCancelarId');
        $componente->call('prepararCancelacion', 'no-numérico')->assertHasErrors('pagoCancelarId');
        $componente->call('prepararCancelacion', 999999)->assertHasErrors('pagoCancelarId');
    }

    public function test_signed_selection_prevents_substitution_with_another_valid_confirmed_payment(): void
    {
        $admin = $this->user('admin');
        $pagoA = $this->pago($admin, ['folio' => 'PAGO-A']);
        $pagoB = $this->pago($admin, ['folio' => 'PAGO-B']);

        $componente = Livewire::actingAs($admin)->test(ShowPagos::class)
            ->call('prepararCancelacion', $pagoA->getKey())
            ->assertSet('mostrarModalCancelacion', true);
        $tokenA = $componente->get('cancelacionToken');
        $this->assertNotEmpty($tokenA);

        $componente->set('pagoCancelarId', $pagoB->getKey())->set('motivoCancelacion', 'Intento manipulado')
            ->call('confirmarCancelacion')->assertHasErrors('pagoCancelarId');

        $this->assertSame(Pago::ESTADO_CONFIRMADO, $pagoA->fresh()->estado);
        $this->assertSame(Pago::ESTADO_CONFIRMADO, $pagoB->fresh()->estado);
    }

    public function test_empty_altered_and_reused_tokens_are_rejected_and_close_clears_state(): void
    {
        $admin = $this->user('admin');
        $pago = $this->pago($admin);
        $componente = Livewire::actingAs($admin)->test(ShowPagos::class)
            ->call('prepararCancelacion', $pago->getKey())->set('motivoCancelacion', 'Motivo válido');

        $componente->set('cancelacionToken', '')->call('confirmarCancelacion')->assertHasErrors('pagoCancelarId');
        $componente->call('prepararCancelacion', $pago->getKey());
        $token = $componente->get('cancelacionToken');
        $componente->set('cancelacionToken', $token.'alterado')->call('confirmarCancelacion')->assertHasErrors('pagoCancelarId');
        $componente->call('cerrarCancelacion')->assertSet('pagoCancelarId', null)
            ->assertSet('motivoCancelacion', '')->assertSet('cancelacionToken', null)
            ->assertSet('mostrarModalCancelacion', false);
        $componente->set('pagoCancelarId', $pago->getKey())->set('cancelacionToken', $token)
            ->set('motivoCancelacion', 'Reutilización')->call('confirmarCancelacion')->assertHasErrors('pagoCancelarId');
        $this->assertSame(Pago::ESTADO_CONFIRMADO, $pago->fresh()->estado);
    }

    private function pago(User $usuario, array $cambios = []): Pago
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $prospecto->update(['prospectos_apellidos' => 'Dupont']);
        $responsable = ResponsablePago::create(['tipo' => 'persona', 'prospectos_id' => $prospecto->getKey(), 'nombre_razon_social' => 'Responsable', 'activo' => true]);
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $inscripcion->update(['responsable_pago_id' => $responsable->getKey()]);
        $atributos = array_merge([
            'folio' => uniqid('PAG-'), 'inscripciones_id' => $inscripcion->getKey(), 'prospectos_id' => $prospecto->getKey(),
            'responsable_pago_id' => $responsable->getKey(), 'fecha_pago' => '2026-09-09 12:00:00', 'zona_horaria' => 'America/Mexico_City',
            'monto' => '100.00', 'metodo_pago_id' => MetodoPago::query()->firstOrFail()->getKey(),
        ], $cambios);
        $estado = $atributos['estado'] ?? Pago::ESTADO_CONFIRMADO;
        unset($atributos['estado']);
        $pago = Pago::create($atributos);
        DB::table('pagos')->where('pago_id', $pago->getKey())->update(['estado' => $estado, 'confirmed_by' => $usuario->getKey(), 'fecha_confirmacion' => '2026-09-09 12:00:00']);

        return $pago->fresh();
    }
}
