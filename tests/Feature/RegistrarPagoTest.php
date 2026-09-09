<?php

namespace Tests\Feature;

use App\Http\Livewire\RegistrarPago;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Inscripcion;
use App\Models\MetodoPago;
use App\Models\ResponsablePago;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use ReflectionClass;
use ReflectionProperty;

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
        $this->get('/facturacion/pagos/registrar')->assertRedirect('/login');

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
        $this->actingAs($withoutRole)->get('/facturacion/pagos/registrar')->assertForbidden();
        Livewire::actingAs($withoutRole)->test(RegistrarPago::class)->assertForbidden();
    }

    public function test_payment_information_is_prepared_without_persistence(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '60.60']);
        $efectivo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();

        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $cargo->getKey())
            ->assertSet('montoRecibido', '60.60')
            ->set('metodoPagoId', $efectivo->getKey())
            ->set('observaciones', '  Pago en recepción  ')
            ->call('prepararPago')
            ->assertSet('montoRecibido', '60.60')
            ->assertSet('observaciones', 'Pago en recepción')
            ->assertSet('mostrarConfirmacion', true)
            ->assertSee('Revisión del pago');

        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('consecutivos_pago', 0);
        $this->assertDatabaseHas('cargos', ['cargo_id' => $cargo->getKey(), 'saldo_pendiente' => '60.60']);
    }

    public function test_transfer_requires_server_configured_fields_and_temporary_receipt(): void
    {
        $cargo = $this->cargo();
        $spei = MetodoPago::where('clave', MetodoPago::TRANSFERENCIA_SPEI)->firstOrFail();
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $spei->getKey())->call('prepararPago')
            ->assertHasErrors(['datosMetodo.banco', 'datosMetodo.referencia', 'datosMetodo.rastreo_spei']);

        $component->set('datosMetodo', ['banco' => 'Banco', 'referencia' => 'REF-1', 'rastreo_spei' => 'SPEI-1', 'proveedor' => 'inyectado'])
            ->set('comprobante', UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf'))
            ->call('prepararPago')->assertSet('mostrarConfirmacion', true)
            ->assertSet('datosMetodo', ['banco' => 'Banco', 'referencia' => 'REF-1', 'rastreo_spei' => 'SPEI-1']);
    }

    public function test_received_amount_must_exactly_match_reconciled_total(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '100.00']);
        $efectivo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('importesAplicar.'.$cargo->getKey(), '33.33')->set('metodoPagoId', $efectivo->getKey())
            ->set('montoRecibido', '33.34')->call('prepararPago')
            ->assertHasErrors('montoRecibido')->assertSet('mostrarConfirmacion', false);
    }

    public function test_changing_payment_data_invalidates_prepared_summary(): void
    {
        $cargo = $this->cargo();
        $efectivo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $efectivo->getKey())->call('prepararPago')->assertSet('mostrarConfirmacion', true)
            ->set('observaciones', 'modificada')->assertSet('mostrarConfirmacion', false);
    }

    public function test_navigation_link_is_visible_only_to_authorized_role(): void
    {
        $this->actingAs($this->user('admin'));
        $this->assertStringContainsString('Registrar pago', Blade::render('<x-layout.aside />'));

        foreach (['venta', 'profe', 'alum'] as $role) {
            $this->actingAs($this->user($role));
            $this->assertStringNotContainsString('Registrar pago', Blade::render('<x-layout.aside />'));
        }
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
            ->call('limpiarSeleccion')->assertSet('inscripcionSeleccionadaId', null);

        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)->call('seleccionarInscripcion', '1 OR 1=1')->assertNotFound();
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)->call('seleccionarInscripcion', 999999)->assertNotFound();
    }

    public function test_failed_selection_clears_previous_financial_data(): void
    {
        $this->cargo(['saldo_pendiente' => '35.50']);
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->assertSee('MXN $35.50');

        $component->call('seleccionarInscripcion', 999999)->assertNotFound();

        $component->set('inscripcionSeleccionadaId', 999999)
            ->assertSet('inscripcionSeleccionadaId', null)
            ->assertDontSee('MXN $35.50');
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
            ->assertViewHas('resumen', fn ($resumen) => $resumen === [
                'saldoPendiente' => '60.60', 'saldoVencido' => '50.50',
                'cantidadCargosAbiertos' => 3, 'cantidadCargosVencidos' => 2,
                'proximoVencimiento' => '2026-09-09',
            ])
            ->assertSee('MXN $60.60')->assertDontSee('MXN $40.40');
    }

    public function test_orders_overdue_first_then_due_date_and_formats_periods(): void
    {
        $futuro = $this->cargo(['fecha_vencimiento' => '2026-10-01', 'periodo_anio' => 2026, 'periodo_mes' => 10]);
        $actual = $this->cargo(['fecha_vencimiento' => '2026-09-09']);
        $vencidoEstado = $this->cargo(['estado' => 'vencido', 'fecha_vencimiento' => '2026-09-08']);
        $vencidoFechaA = $this->cargo(['fecha_vencimiento' => '2026-09-01', 'periodo_anio' => null, 'periodo_mes' => null]);
        $vencidoFechaB = $this->cargo(['fecha_vencimiento' => '2026-09-01']);
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->assertViewHas('cargos', fn ($cargos) => $cargos->pluck('cargo_id')->all() === [
                $vencidoFechaA->cargo_id, $vencidoFechaB->cargo_id, $vencidoEstado->cargo_id,
                $actual->cargo_id, $futuro->cargo_id,
            ])->assertSee('Sin periodo')->assertSee('2026-10');
    }

    public function test_tampered_selection_values_are_cleared_without_query_errors_or_stale_data(): void
    {
        $this->cargo(['saldo_pendiente' => '35.50']);
        foreach (['texto', ['id' => 1], 0, -1, 999999] as $value) {
            Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
                ->set('inscripcionSeleccionadaId', $value)
                ->assertSet('inscripcionSeleccionadaId', null)
                ->assertDontSee('MXN $35.50');
        }
    }

    public function test_direct_valid_id_is_requeried_and_deleted_or_orphaned_enrollment_is_cleared(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $prospecto->update(['prospectos_nombres' => 'Servidor Dos']);
        $other = $this->enroll($prospecto, $curso, $grupo);
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->set('inscripcionSeleccionadaId', $other->getKey())->assertSee('Servidor Dos');
        $other->delete();
        $component->set('busqueda', 'refrescar')->assertSet('inscripcionSeleccionadaId', null)->assertDontSee('Servidor Dos');

        $this->inscripcion->update(['prospectos_id' => 999999]);
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->set('inscripcionSeleccionadaId', $this->inscripcion->getKey())
            ->assertSet('inscripcionSeleccionadaId', null);
    }

    public function test_summary_is_recalculated_and_cannot_be_forged_as_livewire_state(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '10.01']);
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->assertSee('MXN $10.01');
        $cargo->update(['saldo_pendiente' => '25.99']);
        $component->set('busqueda', 'render')->assertSee('MXN $25.99')->assertDontSee('MXN $10.01');

        $public = collect((new \ReflectionClass(RegistrarPago::class))->getProperties(\ReflectionProperty::IS_PUBLIC))->pluck('name');
        foreach (['saldoPendiente', 'saldoVencido', 'cantidadCargosAbiertos', 'cantidadCargosVencidos', 'proximoVencimiento'] as $property) {
            $this->assertNotContains($property, $public);
        }
    }

    public function test_empty_message_inactive_warning_and_each_non_open_charge_is_hidden(): void
    {
        $this->inscripcion->responsablePago->update(['activo' => false]);
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->assertSee('El responsable de pago está inactivo.')->assertSee('No hay cargos pendientes.');

        foreach ([['pagado', '41.01'], ['cancelado', '42.02'], ['pendiente', '0.00']] as [$estado, $saldo]) {
            $this->cargo(['estado' => $estado, 'saldo_pendiente' => $saldo, 'total' => $saldo]);
        }
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->assertDontSee('MXN $41.01')->assertDontSee('MXN $42.02')->assertSee('No hay cargos pendientes.');
    }

    public function test_query_actions_and_repeated_render_do_not_write_financial_records(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '75.25']);
        $tables = ['pagos', 'consecutivos_pago', 'cargos', 'inscripciones', 'responsables_pago'];
        $before = collect($tables)->mapWithKeys(fn ($table) => [$table => \DB::table($table)->get()->map(fn ($row) => (array) $row)->all()]);
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->set('busqueda', 'Marie')->call('seleccionarInscripcion', $this->inscripcion->getKey());
        $component->assertSee('75.25')->assertSee('75.25')->set('busqueda', 'otra')->call('limpiarSeleccion');
        foreach ($tables as $table) {
            $after = \DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            $this->assertEqualsCanonicalizing($before[$table], $after, "La consulta modificó {$table}.");
        }
        $this->assertDatabaseCount('cargos', 1);
        $this->assertDatabaseCount('responsables_pago', 1);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('consecutivos_pago', 0);
    }

    public function test_selects_one_or_many_eligible_charges_and_proposes_full_balances(): void
    {
        $first = $this->cargo(['saldo_pendiente' => '10.10']);
        $second = $this->cargo(['saldo_pendiente' => '20.20']);
        $third = $this->cargo(['saldo_pendiente' => '30.30']);

        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $first->getKey())
            ->assertSet('cargosSeleccionados', [$first->getKey()])
            ->assertSet('importesAplicar.'.$first->getKey(), '10.10')
            ->call('seleccionarTodosCargos')
            ->assertSet('cargosSeleccionados', [$first->getKey(), $second->getKey(), $third->getKey()])
            ->assertSee('MXN $60.60');
    }

    public function test_validates_and_normalizes_partial_amounts_with_exact_remaining_balance(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '100.00']);
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $cargo->getKey())
            ->set('importesAplicar.'.$cargo->getKey(), '33.33')
            ->assertSet('importesAplicar.'.$cargo->getKey(), '33.33')
            ->assertSee('MXN $66.67');

        foreach (['0', '0.00', '-1', '1.001', '1e2', '1,000', 'texto', 'INF', 'NAN'] as $invalid) {
            $component->set('importesAplicar.'.$cargo->getKey(), $invalid)
                ->assertHasErrors('importesAplicar.'.$cargo->getKey());
        }
        $component->set('importesAplicar.'.$cargo->getKey(), ['manipulado'])
            ->assertHasErrors('importesAplicar.'.$cargo->getKey());
    }

    public function test_common_amount_formats_are_normalized_without_floating_point_calculation(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '200.00']);
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $cargo->getKey());

        foreach (['100' => '100.00', '100.5' => '100.50', '100.50' => '100.50', '0.01' => '0.01'] as $entrada => $normalizado) {
            $component->set('importesAplicar.'.$cargo->getKey(), $entrada)
                ->assertSet('importesAplicar.'.$cargo->getKey(), $normalizado)
                ->assertHasNoErrors('importesAplicar.'.$cargo->getKey());
        }
    }

    public function test_capture_over_balance_stays_selected_but_concurrent_reduction_is_removed(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '100.00']);
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $cargo->getKey())
            ->set('importesAplicar.'.$cargo->getKey(), '100.01')
            ->assertSet('cargosSeleccionados', [$cargo->getKey()])
            ->assertHasErrors('importesAplicar.'.$cargo->getKey());

        $component->set('importesAplicar.'.$cargo->getKey(), '80.00')->assertHasNoErrors();
        $cargo->update(['saldo_pendiente' => '50.00']);
        $component->call('prepararPago')
            ->assertSet('cargosSeleccionados', [])
            ->assertHasErrors('cargosSeleccionados');
    }

    public function test_tampered_non_array_collection_state_is_cleaned_without_exception(): void
    {
        $cargo = $this->cargo();
        foreach (['texto', 10, null] as $valor) {
            Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
                ->call('seleccionarInscripcion', $this->inscripcion->getKey())
                ->set('cargosSeleccionados', $valor)
                ->call('prepararPago')
                ->assertSet('cargosSeleccionados', [])
                ->assertHasErrors('cargosSeleccionados');

            Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
                ->call('seleccionarInscripcion', $this->inscripcion->getKey())
                ->call('seleccionarCargo', $cargo->getKey())
                ->set('importesAplicar', $valor)
                ->call('prepararPago')
                ->assertSet('importesAplicar', [])
                ->assertHasErrors();
        }
    }

    public function test_selection_summary_excludes_directly_injected_foreign_closed_and_wrong_currency_charges(): void
    {
        $valid = $this->cargo(['saldo_pendiente' => '25.00']);
        $closed = $this->cargo(['estado' => 'pagado', 'saldo_pendiente' => '90.00']);
        $currency = $this->cargo(['moneda' => 'USD', 'saldo_pendiente' => '80.00']);
        [$p, $c, $g] = $this->catalogs();
        $other = $this->enroll($p, $c, $g);
        $foreign = $this->cargo(['inscripciones_id' => $other->getKey(), 'saldo_pendiente' => '70.00']);

        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->set('cargosSeleccionados', [$valid->getKey(), $closed->getKey(), $currency->getKey(), $foreign->getKey()])
            ->set('importesAplicar', [
                (string) $valid->getKey() => '10.00', (string) $closed->getKey() => '90.00',
                (string) $currency->getKey() => '80.00', (string) $foreign->getKey() => '70.00',
            ])
            ->assertViewHas('resumenSeleccion', fn ($resumen) => $resumen['cantidad'] === 1
                && $resumen['total'] === '10.00' && $resumen['saldoRestante'] === '15.00'
                && $resumen['tieneParciales'] === true);
    }

    public function test_rejects_tampered_ineligible_and_foreign_charge_ids(): void
    {
        [$p, $c, $g] = $this->catalogs();
        $other = $this->enroll($p, $c, $g);
        $foreign = $this->cargo(['inscripciones_id' => $other->getKey()]);
        $paid = $this->cargo(['estado' => 'pagado']);
        $cancelled = $this->cargo(['estado' => 'cancelado']);
        $zero = $this->cargo(['saldo_pendiente' => '0.00']);

        foreach ([$foreign->getKey(), $paid->getKey(), $cancelled->getKey(), $zero->getKey(), 999999, -1, '1 OR 1=1', ['id' => 1]] as $id) {
            Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
                ->call('seleccionarInscripcion', $this->inscripcion->getKey())
                ->call('seleccionarCargo', $id)
                ->assertSet('cargosSeleccionados', [])
                ->assertHasErrors('cargosSeleccionados');
        }
    }

    public function test_deselect_and_enrollment_change_clear_amounts_and_errors(): void
    {
        $cargo = $this->cargo();
        [$p, $c, $g] = $this->catalogs();
        $other = $this->enroll($p, $c, $g);
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $cargo->getKey())
            ->set('importesAplicar.'.$cargo->getKey(), '0')
            ->assertHasErrors('importesAplicar.'.$cargo->getKey())
            ->call('deseleccionarCargo', $cargo->getKey())
            ->assertSet('cargosSeleccionados', [])->assertSet('importesAplicar', [])->assertHasNoErrors()
            ->call('seleccionarInscripcion', $other->getKey())
            ->assertSet('cargosSeleccionados', [])->assertSet('importesAplicar', []);
    }

    public function test_concurrent_changes_remove_stale_selection_and_new_actions_reauthorize(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '100.00']);
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $cargo->getKey());
        $cargo->update(['saldo_pendiente' => '50.00']);
        $component->call('prepararPago')->assertSet('cargosSeleccionados', [])->assertSet('importesAplicar', []);

        $this->actingAs($this->user('venta'));
        foreach (['seleccionarCargo', 'deseleccionarCargo', 'seleccionarTodosCargos', 'limpiarSeleccionCargos', 'prepararPago', 'updatedImportesAplicar'] as $method) {
            try {
                $arguments = in_array($method, ['seleccionarCargo', 'deseleccionarCargo'], true) ? [$cargo->getKey()] : [];
                if ($method === 'updatedImportesAplicar') {
                    $arguments = ['10.00', (string) $cargo->getKey()];
                }
                (new RegistrarPago())->{$method}(...$arguments);
                $this->fail("{$method} no rechazó al usuario.");
            } catch (AuthorizationException $exception) {
                $this->assertInstanceOf(AuthorizationException::class, $exception);
            }
        }
    }

    public function test_concurrent_paid_status_removes_selected_charge_and_its_amount(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '81.25']);
        $component = $this->componentWithSelectedCharge($cargo);

        $cargo->update(['estado' => Cargo::ESTADO_PAGADO]);

        $component->call('prepararPago')
            ->assertSet('cargosSeleccionados', [])
            ->assertSet('importesAplicar', [])
            ->assertSee('Un cargo seleccionado ya no está disponible y fue retirado.');
    }

    public function test_concurrent_cancelled_status_removes_selected_charge_and_its_amount(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '82.25']);
        $component = $this->componentWithSelectedCharge($cargo);

        $cargo->update(['estado' => Cargo::ESTADO_CANCELADO]);

        $component->call('prepararPago')
            ->assertSet('cargosSeleccionados', [])
            ->assertSet('importesAplicar', [])
            ->assertSee('Un cargo seleccionado ya no está disponible y fue retirado.');
    }

    public function test_concurrent_enrollment_change_removes_selected_charge_and_its_amount(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '83.25']);
        $component = $this->componentWithSelectedCharge($cargo);
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $other = $this->enroll($prospecto, $curso, $grupo);

        $cargo->update(['inscripciones_id' => $other->getKey()]);

        $component->call('prepararPago')
            ->assertSet('cargosSeleccionados', [])
            ->assertSet('importesAplicar', [])
            ->assertSee('Un cargo seleccionado ya no está disponible y fue retirado.');
    }

    public function test_concurrent_currency_change_removes_selected_charge_and_its_amount(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '84.25']);
        $component = $this->componentWithSelectedCharge($cargo);

        $cargo->update(['moneda' => 'USD']);

        $component->call('prepararPago')
            ->assertSet('cargosSeleccionados', [])
            ->assertSet('importesAplicar', [])
            ->assertSee('Un cargo seleccionado ya no está disponible y fue retirado.');
    }

    public function test_concurrent_hard_deletion_removes_selected_charge_and_its_amount(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '85.25']);
        $component = $this->componentWithSelectedCharge($cargo);

        $cargo->delete();

        $component->call('prepararPago')
            ->assertSet('cargosSeleccionados', [])
            ->assertSet('importesAplicar', [])
            ->assertSee('Un cargo seleccionado ya no está disponible y fue retirado.');
        $this->assertDatabaseMissing('cargos', ['cargo_id' => $cargo->getKey()]);
    }

    public function test_select_all_keeps_only_eligible_charge_from_mixed_data(): void
    {
        $eligible = $this->cargo(['saldo_pendiente' => '47.30']);
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $other = $this->enroll($prospecto, $curso, $grupo);
        $foreign = $this->cargo(['inscripciones_id' => $other->getKey(), 'saldo_pendiente' => '91.00']);
        $closed = $this->cargo(['estado' => Cargo::ESTADO_PAGADO, 'saldo_pendiente' => '92.00']);
        $zero = $this->cargo(['saldo_pendiente' => '0.00']);
        $currency = $this->cargo(['moneda' => 'USD', 'saldo_pendiente' => '93.00']);

        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarTodosCargos')
            ->assertSet('cargosSeleccionados', [$eligible->getKey()])
            ->assertSet('importesAplicar', [(string) $eligible->getKey() => '47.30'])
            ->assertViewHas('resumenSeleccion', fn ($resumen) => $resumen === [
                'cantidad' => 1,
                'total' => '47.30',
                'moneda' => 'MXN',
                'tieneParciales' => false,
                'saldoRestante' => '0.00',
                'restantes' => [$eligible->getKey() => '0.00'],
            ])
            ->assertDontSee('MXN $91.00')
            ->assertDontSee('MXN $92.00')
            ->assertDontSee('USD $93.00');

        $this->assertNotContains($foreign->getKey(), [$eligible->getKey()]);
        $this->assertNotContains($closed->getKey(), [$eligible->getKey()]);
        $this->assertNotContains($zero->getKey(), [$eligible->getKey()]);
        $this->assertNotContains($currency->getKey(), [$eligible->getKey()]);
    }

    public function test_calculated_and_trusted_values_are_not_public_component_state(): void
    {
        $publicProperties = collect((new ReflectionClass(RegistrarPago::class))
            ->getProperties(ReflectionProperty::IS_PUBLIC))
            ->pluck('name');

        foreach ([
            'totalAplicado', 'totalSeleccionado', 'saldoRestante', 'saldoRestanteTotal',
            'cantidadCargos', 'cantidadSeleccionados', 'tieneParciales', 'pagoParcial',
            'moneda', 'monedaConfiable', 'resumenSeleccion',
        ] as $calculatedProperty) {
            $this->assertNotContains($calculatedProperty, $publicProperties);
        }
    }

    public function test_all_selection_actions_only_change_temporary_livewire_state(): void
    {
        $first = $this->cargo(['saldo_pendiente' => '61.20']);
        $second = $this->cargo(['saldo_pendiente' => '38.80']);
        $beforePagos = DB::table('pagos')->orderBy('pago_id')->get()->map(fn ($row) => (array) $row)->all();
        $beforeConsecutivos = DB::table('consecutivos_pago')->orderBy('anio')->get()->map(fn ($row) => (array) $row)->all();
        $beforeCargos = DB::table('cargos')->orderBy('cargo_id')->get()->map(fn ($row) => (array) $row)->all();

        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $first->getKey())
            ->set('importesAplicar.'.$first->getKey(), '20.10')
            ->call('prepararPago')
            ->call('deseleccionarCargo', $first->getKey())
            ->call('seleccionarTodosCargos')
            ->assertSet('cargosSeleccionados', [$first->getKey(), $second->getKey()])
            ->call('limpiarSeleccionCargos')
            ->assertSet('cargosSeleccionados', [])
            ->assertSet('importesAplicar', []);

        $this->assertSame($beforePagos, DB::table('pagos')->orderBy('pago_id')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertSame($beforeConsecutivos, DB::table('consecutivos_pago')->orderBy('anio')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertSame($beforeCargos, DB::table('cargos')->orderBy('cargo_id')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('consecutivos_pago', 0);
    }

    private function componentWithSelectedCharge(Cargo $cargo)
    {
        return Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $cargo->getKey())
            ->assertSet('cargosSeleccionados', [$cargo->getKey()])
            ->assertSet('importesAplicar.'.$cargo->getKey(), $cargo->saldo_pendiente);
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
