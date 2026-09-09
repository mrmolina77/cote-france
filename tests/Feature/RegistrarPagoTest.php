<?php

namespace Tests\Feature;

use App\Http\Livewire\RegistrarPago;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Inscripcion;
use App\Models\ResponsablePago;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

class RegistrarPagoTest extends InscripcionesTestCase
{
    private $inscripcion;
    private $concepto;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-09 12:00:00');
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $prospecto->update(['prospectos_nombres' => '  Marie Claire', 'prospectos_apellidos' => 'Dupont Martin', 'prospectos_correo' => 'alumna@example.test']);
        $responsable = ResponsablePago::create(['tipo' => 'persona', 'prospectos_id' => $prospecto->getKey(), 'nombre_razon_social' => 'Jean Dupont', 'telefono' => null, 'correo' => null, 'activo' => true]);
        $this->inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $this->inscripcion->update(['estatus' => 'activa', 'fecha_inicio' => '2026-09-01', 'fecha_fin' => null, 'responsable_pago_id' => $responsable->getKey()]);
        $this->concepto = ConceptoCobro::where('clave', 'MENSUALIDAD')->first();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_access_is_restricted_to_admins_at_route_gate_and_mount(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->get('/facturacion/pagos/registrar')->assertOk()->assertSee('Registrar pago');
        $this->assertTrue(Gate::forUser($admin)->allows('manage-pagos'));

        foreach (['venta', 'profe', 'alum'] as $role) {
            $user = $this->user($role);
            $this->assertFalse(Gate::forUser($user)->allows('manage-pagos'));
            $this->actingAs($user)->get('/facturacion/pagos/registrar')->assertForbidden();
            Livewire::actingAs($user)->test(RegistrarPago::class)->assertForbidden();
        }

        $withoutRole = User::factory()->create(['roles_id' => 999999]);
        $this->assertFalse(Gate::forUser($withoutRole)->allows('manage-pagos'));
        $this->app['auth']->forgetGuards();
        $this->get('/facturacion/pagos/registrar')->assertRedirect('/login');
    }

    public function test_public_actions_reauthorize_independently(): void
    {
        $this->actingAs($this->user('venta'));
        foreach ([['seleccionarInscripcion', [$this->inscripcion->getKey()]], ['limpiarSeleccion', []], ['updatedBusqueda', []], ['render', []]] as [$method, $arguments]) {
            try {
                (new RegistrarPago())->{$method}(...$arguments);
                $this->fail("{$method} no rechazó al usuario.");
            } catch (AuthorizationException $exception) {
                $this->assertInstanceOf(AuthorizationException::class, $exception);
            }
        }
    }

    public function test_searches_by_exact_id_names_surnames_and_multiple_trimmed_words(): void
    {
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class);
        foreach ([(string) $this->inscripcion->getKey(), 'Marie', 'Martin', '  Claire   Dupont  '] as $term) {
            $component->set('busqueda', $term)->assertSee('Marie Claire')->assertSee('Dupont Martin');
        }
        $component->set('busqueda', 'Inexistente')->assertSee('No existen coincidencias.')->assertDontSee('Dupont Martin');
    }

    public function test_search_excludes_soft_deleted_limits_results_and_treats_injection_as_text(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $prospecto->update(['prospectos_nombres' => 'Oculto']);
        $deleted = $this->enroll($prospecto, $curso, $grupo);
        $deleted->delete();
        for ($i = 1; $i <= 30; $i++) {
            [$p, $c, $g] = $this->catalogs();
            $p->update(['prospectos_nombres' => 'Limite Comun '.$i]);
            $this->enroll($p, $c, $g);
        }

        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->set('busqueda', 'Oculto')->assertSee('No existen coincidencias.')
            ->set('busqueda', 'Limite Comun')->assertViewHas('resultados', fn ($items) => $items->count() === 25)
            ->set('busqueda', "%' OR 1=1; DROP TABLE cargos; --")->assertSee('No existen coincidencias.');
        $this->assertTrue(\Schema::hasTable('cargos'));
    }

    public function test_selection_is_server_validated_can_be_cleared_and_search_does_not_replace_it(): void
    {
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->assertSet('inscripcionSeleccionadaId', $this->inscripcion->getKey())
            ->set('busqueda', 'nadie')->assertSet('inscripcionSeleccionadaId', $this->inscripcion->getKey())
            ->call('limpiarSeleccion')->assertSet('inscripcionSeleccionadaId', null)->assertSet('saldoPendiente', '0.00');

        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)->call('seleccionarInscripcion', '1 OR 1=1')->assertNotFound();
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)->call('seleccionarInscripcion', 999999)->assertNotFound();
    }

    public function test_failed_selection_clears_previous_financial_data(): void
    {
        $this->cargo(['saldo_pendiente' => '35.50']);
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->assertSet('saldoPendiente', '35.50');
        try {
            $component->call('seleccionarInscripcion', 999999);
        } catch (\Throwable $exception) {
            // Livewire converts this abort to a test response in some supported versions.
        }
        $component->assertSet('inscripcionSeleccionadaId', null)->assertSet('saldoPendiente', '0.00');
    }

    public function test_displays_student_course_group_responsible_and_tolerates_optional_nulls(): void
    {
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->assertSee('Marie Claire')->assertSee('Dupont Martin')->assertSee('Francés')->assertSee('A1')->assertSee('Jean Dupont')->assertSee('Sin dato')->assertSee('Sin fecha');

        $this->inscripcion->update(['responsable_pago_id' => null]);
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->assertSee('no tiene un responsable de pago válido');
    }

    public function test_financial_summary_uses_only_open_positive_persisted_charges_with_decimal_precision(): void
    {
        $this->cargo(['estado' => 'pendiente', 'saldo_pendiente' => '10.10', 'fecha_vencimiento' => '2026-09-09']);
        $this->cargo(['estado' => 'parcial', 'saldo_pendiente' => '20.20', 'fecha_vencimiento' => '2026-09-08']);
        $this->cargo(['estado' => 'vencido', 'saldo_pendiente' => '30.30', 'fecha_vencimiento' => '2026-09-20']);
        $this->cargo(['estado' => 'pagado', 'saldo_pendiente' => '40.40']);
        $this->cargo(['estado' => 'cancelado', 'saldo_pendiente' => '50.50']);
        $this->cargo(['estado' => 'pendiente', 'saldo_pendiente' => '0.00']);
        [$p, $c, $g] = $this->catalogs();
        $other = $this->enroll($p, $c, $g);
        $this->cargo(['inscripciones_id' => $other->getKey(), 'saldo_pendiente' => '99.99']);

        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->assertSet('saldoPendiente', '60.60')->assertSet('saldoVencido', '50.50')
            ->assertSet('cantidadCargosAbiertos', 3)->assertSet('cantidadCargosVencidos', 2)
            ->assertSet('proximoVencimiento', '2026-09-09')
            ->assertSee('MXN $60.60')->assertDontSee('MXN $40.40');
    }

    public function test_orders_overdue_first_then_due_date_and_formats_periods(): void
    {
        $this->cargo(['observaciones' => 'Futuro', 'fecha_vencimiento' => '2026-10-01', 'periodo_anio' => 2026, 'periodo_mes' => 10]);
        $this->cargo(['observaciones' => 'Pasado', 'fecha_vencimiento' => '2026-09-01', 'periodo_anio' => null, 'periodo_mes' => null]);
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->assertSeeInOrder(['2026-09-01', '2026-10-01'])->assertSee('Sin periodo')->assertSee('2026-10');
    }

    public function test_query_actions_and_repeated_render_do_not_write_financial_records(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '75.25']);
        $before = $cargo->fresh()->getRawOriginal();
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->set('busqueda', 'Marie')->call('seleccionarInscripcion', $this->inscripcion->getKey());
        $component->assertSee('75.25')->assertSee('75.25')->set('busqueda', 'otra')->call('limpiarSeleccion');
        $this->assertSame($before, $cargo->fresh()->getRawOriginal());
        $this->assertDatabaseCount('cargos', 1);
        $this->assertDatabaseCount('responsables_pago', 1);
    }

    private function cargo(array $overrides = []): Cargo
    {
        return Cargo::create(array_merge([
            'inscripciones_id' => $this->inscripcion->getKey(), 'concepto_cobro_id' => $this->concepto->getKey(),
            'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-30', 'moneda' => 'MXN',
            'subtotal' => '100.00', 'descuento' => '0.00', 'recargo' => '0.00', 'impuestos' => '0.00',
            'total' => '100.00', 'saldo_pendiente' => '100.00', 'estado' => 'pendiente', 'origen' => 'manual',
        ], $overrides));
    }
}
