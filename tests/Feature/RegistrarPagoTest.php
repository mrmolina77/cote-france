<?php

namespace Tests\Feature;

use App\Http\Livewire\RegistrarPago;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Inscripcion;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\ResponsablePago;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $this->assertDatabaseCount('pago_aplicaciones', 0);
        $this->assertDatabaseCount('consecutivos_pago', 0);
        $this->assertDatabaseHas('cargos', ['cargo_id' => $cargo->getKey(), 'saldo_pendiente' => '60.60']);
    }

    public function test_reviewed_payment_can_be_confirmed_only_once_and_clears_the_form(): void
    {
        $cargo = $this->cargo(['saldo_pendiente' => '60.60']);
        $efectivo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();

        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $efectivo->getKey())
            ->call('prepararPago')
            ->assertSee('Confirmar y registrar pago')
            ->call('confirmarPago')
            ->assertSet('mostrarConfirmacion', false)
            ->assertSet('confirmacionFingerprint', null)
            ->assertSet('inscripcionSeleccionadaId', null)
            ->assertSet('cargosSeleccionados', [])
            ->assertSee('Pago registrado correctamente. Folio:');

        $pago = Pago::query()->sole();
        $this->assertSame(Pago::ESTADO_CONFIRMADO, $pago->estado);
        $this->assertNotEmpty($pago->folio);
        $this->assertDatabaseHas('pago_aplicaciones', [
            'pago_id' => $pago->getKey(),
            'cargo_id' => $cargo->getKey(),
            'importe_aplicado' => '60.60',
            'saldo_anterior' => '60.60',
            'saldo_posterior' => '0.00',
        ]);
        $this->assertDatabaseHas('cargos', ['cargo_id' => $cargo->getKey(), 'saldo_pendiente' => '0.00', 'estado' => Cargo::ESTADO_PAGADO]);

        $component->call('confirmarPago')->assertHasErrors('confirmacion');
        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseCount('pago_aplicaciones', 1);
        $this->assertDatabaseCount('consecutivos_pago', 1);
        $component->assertDontSee('Pago registrado correctamente. Folio:');
    }

    public function test_success_is_server_flash_with_the_persisted_escaped_folio(): void
    {
        $this->assertFalse((new ReflectionClass(RegistrarPago::class))->hasProperty('mensajeConfirmacion'));
        $cargo = $this->cargo(['saldo_pendiente' => '18.25']);
        $efectivo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();

        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->assertDontSee('Pago registrado correctamente.')
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $efectivo->getKey())
            ->call('prepararPago')->call('confirmarPago');

        $folio = Pago::query()->sole()->folio;
        $this->assertSame($folio, session('pago_confirmado.folio'));
        $this->assertSame('Pago registrado correctamente.', session('pago_confirmado.mensaje'));
        $this->assertStringContainsString(
            'Pago registrado correctamente. Folio: '.$folio,
            Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)->html()
        );

        session()->flash('pago_confirmado', ['mensaje' => '<script>alert(1)</script>', 'folio' => '<b>FOLIO</b>']);
        $html = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)->html();
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt; Folio: &lt;b&gt;FOLIO&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_livewire_confirmation_applies_full_and_partial_amounts_exactly(): void
    {
        $full = $this->cargo(['total' => '30.30', 'saldo_pendiente' => '30.30']);
        $partial = $this->cargo(['total' => '70.70', 'saldo_pendiente' => '70.70']);
        $cash = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();

        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarTodosCargos')
            ->set('importesAplicar.'.$partial->getKey(), '20.20')
            ->set('montoRecibido', '50.50')->set('metodoPagoId', $cash->getKey())
            ->call('prepararPago')->assertSet('mostrarConfirmacion', true)
            ->call('confirmarPago')->assertHasNoErrors()->assertSee('Pago registrado correctamente. Folio:');

        $pago = Pago::query()->sole();
        $this->assertSame('50.50', $pago->monto);
        $this->assertDatabaseCount('pago_aplicaciones', 2);
        $this->assertDatabaseHas('pago_aplicaciones', ['pago_id' => $pago->getKey(), 'cargo_id' => $full->getKey(), 'importe_aplicado' => '30.30', 'saldo_anterior' => '30.30', 'saldo_posterior' => '0.00']);
        $this->assertDatabaseHas('pago_aplicaciones', ['pago_id' => $pago->getKey(), 'cargo_id' => $partial->getKey(), 'importe_aplicado' => '20.20', 'saldo_anterior' => '70.70', 'saldo_posterior' => '50.50']);
        $this->assertSame(Cargo::ESTADO_PAGADO, $full->fresh()->estado);
        $this->assertSame(Cargo::ESTADO_PARCIAL, $partial->fresh()->estado);
        $this->assertSame($pago->folio, session('pago_confirmado.folio'));
    }

    public function test_confirmation_without_a_valid_review_does_not_write_financial_records(): void
    {
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('confirmarPago')
            ->assertHasErrors('confirmacion')
            ->assertDontSee('Pago registrado correctamente. Folio:');

        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('pago_aplicaciones', 0);
        $this->assertDatabaseCount('consecutivos_pago', 0);
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
            ->assertSet('datosMetodo', ['banco' => 'Banco', 'referencia' => 'REF-1', 'rastreo_spei' => 'SPEI-1', 'forma_pago_sat' => '03']);
    }

    public function test_required_receipt_errors_use_the_visible_property_and_validate_type_and_size(): void
    {
        $cargo = $this->cargo();
        $spei = MetodoPago::where('clave', MetodoPago::TRANSFERENCIA_SPEI)->firstOrFail();
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $spei->getKey())
            ->set('datosMetodo', ['banco' => 'Banco', 'referencia' => 'REF', 'rastreo_spei' => 'SPEI']);

        $component->call('prepararPago')
            ->assertHasErrors('comprobante')->assertHasNoErrors('datosMetodo.comprobante')
            ->assertSet('mostrarConfirmacion', false)
            ->set('comprobante', UploadedFile::fake()->create('virus.exe', 20, 'application/octet-stream'))
            ->call('prepararPago')->assertHasErrors('comprobante')->assertSet('mostrarConfirmacion', false)
            ->set('comprobante', UploadedFile::fake()->create('grande.pdf', 10241, 'application/pdf'))
            ->call('prepararPago')->assertHasErrors('comprobante')->assertSet('mostrarConfirmacion', false)
            ->set('comprobante', UploadedFile::fake()->create('valido.pdf', 100, 'application/pdf'))
            ->call('prepararPago')->assertHasNoErrors('comprobante')->assertSet('mostrarConfirmacion', true);
    }

    public function test_method_change_clears_dynamic_errors_receipt_and_injected_hidden_fields(): void
    {
        $cargo = $this->cargo();
        $spei = MetodoPago::where('clave', MetodoPago::TRANSFERENCIA_SPEI)->firstOrFail();
        $efectivo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $spei->getKey())->call('prepararPago')
            ->assertHasErrors(['datosMetodo.banco', 'datosMetodo.referencia', 'datosMetodo.rastreo_spei', 'comprobante'])
            ->set('comprobante', UploadedFile::fake()->create('temporal.pdf', 20, 'application/pdf'))
            ->set('metodoPagoId', $efectivo->getKey())
            ->assertSet('datosMetodo', [])->assertSet('comprobante', null)->assertHasNoErrors()
            ->set('datosMetodo', ['banco' => 'inyectado', 'referencia' => 'oculta'])
            ->set('comprobante', UploadedFile::fake()->create('inyectado.pdf', 20, 'application/pdf'))
            ->call('prepararPago')->assertSet('datosMetodo', ['forma_pago_sat' => '01'])->assertSet('comprobante', null)
            ->assertSet('mostrarConfirmacion', true);
    }

    public function test_direct_confirmation_flag_cannot_display_an_unvalidated_review(): void
    {
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->set('mostrarConfirmacion', true)
            ->assertDontSee('Revisión del pago');

        $cargo = $this->cargo();
        $efectivo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $efectivo->getKey())->call('prepararPago')->assertSee('Revisión del pago');

        $component->set('montoRecibido', '0.01')->set('mostrarConfirmacion', true)
            ->assertDontSee('Revisión del pago');
    }

    public function test_duplicate_warning_uses_identifiers_not_bank_and_ignores_cancelled_payments(): void
    {
        $cargo = $this->cargo();
        $spei = MetodoPago::where('clave', MetodoPago::TRANSFERENCIA_SPEI)->firstOrFail();
        $this->existingPayment($spei, ['banco' => 'Banco Uno', 'referencia' => 'OTRA', 'rastreo_spei' => 'OTRO']);

        $base = fn () => Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $spei->getKey())
            ->set('comprobante', UploadedFile::fake()->create('comprobante.pdf', 20, 'application/pdf'));

        $base()->set('datosMetodo', ['banco' => 'Banco Uno', 'referencia' => 'NUEVA', 'rastreo_spei' => 'NUEVO'])
            ->call('prepararPago')->assertDontSee('identificador coincidente')->assertSet('mostrarConfirmacion', true);
        $base()->set('datosMetodo', ['banco' => 'Otro banco', 'referencia' => ' otra ', 'rastreo_spei' => 'NUEVO'])
            ->call('prepararPago')->assertSee('identificador coincidente')->assertSet('mostrarConfirmacion', true)
            ->assertViewHas('advertenciaDuplicidad', function ($advertencia) {
                return ! str_contains($advertencia, 'Marie Claire') && ! str_contains($advertencia, 'Jean Dupont')
                    && ! str_contains($advertencia, '100.00');
            });

        $cancelado = $this->existingPayment($spei, ['folio' => 'PAG-2026-999998', 'referencia' => 'CANCELADA', 'rastreo_spei' => 'CANCELADO']);
        $cancelado->forceFill(['estado' => Pago::ESTADO_CANCELADO])->save();
        $base()->set('datosMetodo', ['banco' => 'Banco Uno', 'referencia' => 'CANCELADA', 'rastreo_spei' => 'SIN-DUPLICAR'])
            ->call('prepararPago')->assertDontSee('identificador coincidente')->assertSet('mostrarConfirmacion', true);
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
        $users = collect(['venta', 'profe', 'alum'])->map(fn ($role) => $this->user($role));
        $users->push(User::factory()->create(['roles_id' => 999999]));

        foreach ($users as $user) {
            $this->actingAs($user);
            foreach ([['seleccionarInscripcion', [$this->inscripcion->getKey()]], ['limpiarSeleccion', []], ['updatedBusqueda', []], ['confirmarPago', []], ['render', []]] as [$method, $arguments]) {
                try {
                    (new RegistrarPago())->{$method}(...$arguments);
                    $this->fail("{$method} no rechazó al usuario.");
                } catch (AuthorizationException $exception) {
                    $this->assertInstanceOf(AuthorizationException::class, $exception);
                }
            }
        }
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('pago_aplicaciones', 0);
        $this->assertDatabaseCount('consecutivos_pago', 0);
    }

    public function test_payment_hooks_and_actions_reauthorize_independently(): void
    {
        $this->actingAs($this->user('venta'));
        $calls = [
            ['updated', ['montoRecibido', '10.00']],
            ['updatedMetodoPagoId', []],
            ['updatedDatosMetodo', []],
            ['updatedFechaPago', []],
            ['updatedMontoRecibido', []],
            ['updatedObservaciones', []],
            ['updatedComprobante', []],
            ['prepararPago', []],
            ['volverAEditar', []],
        ];
        foreach ($calls as [$method, $arguments]) {
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
            'mensajeConfirmacion',
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

    public function test_only_active_methods_are_displayed_in_persisted_order(): void
    {
        $first = MetodoPago::where('clave', MetodoPago::POR_DEFINIR)->firstOrFail();
        $last = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $inactive = MetodoPago::where('clave', MetodoPago::CHEQUE_NOMINATIVO)->firstOrFail();
        $first->update(['orden' => 1, 'nombre' => 'Primero persistido']);
        $last->update(['orden' => 999, 'nombre' => 'Último persistido']);
        $inactive->update(['activo' => false, 'nombre' => 'Método oculto']);

        $cargo = $this->cargo();
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('seleccionarCargo', $cargo->getKey())
            ->assertViewHas('metodosPago', fn ($methods) => $methods->first()->is($first)
                && $methods->last()->is($last) && ! $methods->contains($inactive))
            ->assertSeeInOrder(['Primero persistido', 'Último persistido'])
            ->assertDontSee('Método oculto');
    }

    public function test_tampered_method_identifiers_are_rejected_by_livewire_integration(): void
    {
        $cargo = $this->cargo();
        $inactive = MetodoPago::where('clave', MetodoPago::CHEQUE_NOMINATIVO)->firstOrFail();
        $inactive->update(['activo' => false]);
        foreach ([null, '', 'texto', ['id' => 1], 0, -1, 999999, $inactive->getKey()] as $value) {
            Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
                ->call('seleccionarInscripcion', $this->inscripcion->getKey())
                ->call('seleccionarCargo', $cargo->getKey())
                ->set('metodoPagoId', $value)->call('prepararPago')
                ->assertHasErrors('metodoPagoId')->assertSet('mostrarConfirmacion', false)
                ->assertSet('confirmacionFingerprint', null);
        }
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('consecutivos_pago', 0);
    }

    public function test_dynamic_fields_follow_persisted_flags_instead_of_method_key(): void
    {
        $cargo = $this->cargo();
        $cash = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $cash->update(['requiere_banco' => true, 'requiere_referencia' => true]);

        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $cash->getKey())
            ->assertSee('Banco')->assertSee('Referencia')->call('prepararPago')
            ->assertHasErrors(['datosMetodo.banco', 'datosMetodo.referencia']);

        $cash->update(['requiere_banco' => false, 'requiere_referencia' => false]);
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $cash->getKey())
            ->set('datosMetodo', ['banco' => 'inyectado', 'referencia' => 'inyectada'])
            ->call('prepararPago')->assertSet('datosMetodo', ['forma_pago_sat' => '01'])->assertSet('mostrarConfirmacion', true);
    }

    public function test_bank_deposit_requires_exactly_two_sat_digits_and_accepts_valid_data(): void
    {
        $cargo = $this->cargo();
        $deposit = MetodoPago::where('clave', MetodoPago::DEPOSITO_BANCARIO)->firstOrFail();
        $base = fn () => Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $deposit->getKey())
            ->set('comprobante', UploadedFile::fake()->create('deposito.pdf', 10, 'application/pdf'));

        $base()->assertSee('Forma de pago SAT')->assertSee('Banco')->assertSee('Referencia')
            ->assertSee('Comprobante obligatorio');
        foreach ([null, '', '1', '123', 'AA', ['03']] as $invalid) {
            $base()->set('datosMetodo', ['forma_pago_sat' => $invalid, 'banco' => 'Banco', 'referencia' => 'DEP-1'])
                ->call('prepararPago')->assertHasErrors('datosMetodo.forma_pago_sat')->assertSet('mostrarConfirmacion', false);
        }
        $base()->set('datosMetodo', ['forma_pago_sat' => '03', 'banco' => 'Banco', 'referencia' => 'DEP-1'])
            ->call('prepararPago')->assertHasNoErrors()->assertSet('mostrarConfirmacion', true)->assertSee('Revisión del pago');
    }

    public function test_fixed_sat_value_is_trusted_from_database_and_cannot_be_overridden(): void
    {
        $cargo = $this->cargo();
        $cash = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $cash->update(['clave_forma_pago_sat' => '77', 'requiere_forma_pago_sat' => true]);
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $cash->getKey())->assertSee('Forma de pago SAT:')->assertSee('77')
            ->set('datosMetodo', ['forma_pago_sat' => '99'])->call('prepararPago')
            ->assertSet('datosMetodo', ['forma_pago_sat' => '77'])->assertSet('mostrarConfirmacion', true)
            ->assertSee('Forma de pago SAT')->assertSee('77');
    }

    public function test_cards_require_persisted_fields_validate_last_four_and_discard_hidden_data(): void
    {
        $cargo = $this->cargo();
        foreach ([MetodoPago::TARJETA_CREDITO, MetodoPago::TARJETA_DEBITO] as $key) {
            $card = MetodoPago::where('clave', $key)->firstOrFail();
            $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
                ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
                ->set('metodoPagoId', $card->getKey())->call('prepararPago')
                ->assertHasErrors(['datosMetodo.numero_autorizacion', 'datosMetodo.terminal', 'datosMetodo.ultimos_4_digitos']);
            foreach (['123', '12345', '12A4'] as $invalid) {
                $component->set('datosMetodo', ['numero_autorizacion' => 'AUT', 'terminal' => 'T1', 'ultimos_4_digitos' => $invalid])
                    ->call('prepararPago')->assertHasErrors('datosMetodo.ultimos_4_digitos');
            }
            $component->set('datosMetodo', ['numero_autorizacion' => 'AUT', 'terminal' => 'T1', 'ultimos_4_digitos' => '1234', 'banco' => 'oculto'])
                ->call('prepararPago')->assertSet('datosMetodo', ['numero_autorizacion' => 'AUT', 'terminal' => 'T1', 'ultimos_4_digitos' => '1234', 'forma_pago_sat' => $card->clave_forma_pago_sat])
                ->assertSet('mostrarConfirmacion', true);
        }
    }

    public function test_advance_application_cannot_be_enabled_by_tampered_dynamic_data(): void
    {
        $cargo = $this->cargo();
        $advance = MetodoPago::where('clave', MetodoPago::APLICACION_ANTICIPO)->firstOrFail();
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $advance->getKey())->set('datosMetodo', ['anticipo_relacionado_id' => 1])
            ->call('prepararPago')->assertHasErrors('metodoPagoId')->assertSee('se habilitará en el bloque correspondiente')
            ->assertSet('mostrarConfirmacion', false)->assertSet('datosMetodo', []);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('consecutivos_pago', 0);
        $this->assertDatabaseHas('cargos', ['cargo_id' => $cargo->getKey(), 'saldo_pendiente' => '100.00']);
    }

    public function test_received_amount_normalization_and_invalid_values_during_preparation(): void
    {
        $cash = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        foreach (['100' => '100.00', '100.5' => '100.50', '100.50' => '100.50', '0.01' => '0.01'] as $input => $expected) {
            $cargo = $this->cargo(['saldo_pendiente' => $expected, 'total' => $expected]);
            Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
                ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
                ->set('metodoPagoId', $cash->getKey())->set('montoRecibido', $input)->call('prepararPago')
                ->assertSet('montoRecibido', $expected)->assertSet('mostrarConfirmacion', true);
            $cargo->delete();
        }
        $cargo = $this->cargo();
        foreach (['0', '0.00', '-1', '1.001', '1e2', '1,000', 'texto', '', ['100'], INF, NAN] as $invalid) {
            Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
                ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
                ->set('metodoPagoId', $cash->getKey())->set('montoRecibido', $invalid)->call('prepararPago')
                ->assertHasErrors('montoRecibido')->assertSet('mostrarConfirmacion', false)->assertSet('confirmacionFingerprint', null);
        }
    }

    public function test_multiple_partial_applications_sum_exact_decimal_values(): void
    {
        $charges = collect(['10.10', '20.20', '40.40'])->map(fn ($amount) => $this->cargo(['saldo_pendiente' => $amount, 'total' => $amount]));
        $cash = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarTodosCargos')
            ->set('importesAplicar.'.$charges[2]->getKey(), '30.30')->set('montoRecibido', '60.60')
            ->set('metodoPagoId', $cash->getKey())->call('prepararPago')
            ->assertSet('montoRecibido', '60.60')->assertSet('mostrarConfirmacion', true)
            ->assertViewHas('resumenSeleccion', fn ($summary) => $summary['total'] === '60.60'
                && $summary['saldoRestante'] === '10.10' && $summary['restantes'][$charges[2]->getKey()] === '10.10');
        $component->call('volverAEditar')->set('montoRecibido', '60.59')->call('prepararPago')
            ->assertHasErrors('montoRecibido')->assertSet('mostrarConfirmacion', false);
    }

    public function test_payment_date_boundary_and_malformed_values_are_deterministic(): void
    {
        $cargo = $this->cargo();
        $cash = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        foreach (['2026-09-09T12:00', '2026-09-09T12:05'] as $valid) {
            Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
                ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
                ->set('metodoPagoId', $cash->getKey())->set('fechaPago', $valid)->call('prepararPago')
                ->assertHasNoErrors('fechaPago')->assertSet('mostrarConfirmacion', true);
        }
        foreach (['2026-09-09T12:06', '09/09/2026 12:00', '2026-02-30T12:00', '', 'mañana', ['2026-09-09T12:00']] as $invalid) {
            Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
                ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
                ->set('metodoPagoId', $cash->getKey())->set('fechaPago', $invalid)->call('prepararPago')
                ->assertHasErrors('fechaPago')->assertSet('mostrarConfirmacion', false)->assertDontSee('Revisión del pago');
        }
    }

    public function test_observations_are_normalized_bounded_escaped_and_invalidate_review(): void
    {
        $cargo = $this->cargo();
        $cash = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $base = fn () => Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $cash->getKey());
        $base()->set('observaciones', '  nota  ')->call('prepararPago')->assertSet('observaciones', 'nota');
        $base()->set('observaciones', '   ')->call('prepararPago')->assertSet('observaciones', null);
        $base()->set('observaciones', str_repeat('a', 2000))->call('prepararPago')->assertHasNoErrors('observaciones');
        foreach ([str_repeat('a', 2001), ['manipulada']] as $invalid) {
            $base()->set('observaciones', $invalid)->call('prepararPago')->assertHasErrors('observaciones')->assertSet('mostrarConfirmacion', false);
        }
        $base()->set('observaciones', '<script>alert("x")</script>')->call('prepararPago')
            ->assertDontSee('<script>alert("x")</script>', false)->assertSet('mostrarConfirmacion', true)
            ->set('observaciones', 'cambio')->assertSet('mostrarConfirmacion', false)->assertSet('confirmacionFingerprint', null);
    }

    public function test_concurrent_method_responsible_and_enrollment_changes_block_review_without_writes(): void
    {
        $cash = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $scenarios = [
            'método inactivo' => function () use ($cash) { $cash->update(['activo' => false]); },
            'responsable inactivo' => function () { $this->inscripcion->responsablePago->update(['activo' => false]); },
            'responsable desvinculado' => function () { $this->inscripcion->update(['responsable_pago_id' => null]); },
            'inscripción cancelada' => function () { $this->inscripcion->update(['estatus' => 'cancelada']); },
            'inscripción eliminada' => function () { $this->inscripcion->delete(); },
        ];
        foreach ($scenarios as $name => $change) {
            $this->inscripcion->restore();
            $this->inscripcion->update(['estatus' => 'activa', 'responsable_pago_id' => ResponsablePago::firstOrFail()->getKey()]);
            $this->inscripcion->responsablePago->update(['activo' => true]);
            $cash->update(['activo' => true]);
            Cargo::query()->delete();
            $cargo = $this->cargo();
            $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
                ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
                ->set('metodoPagoId', $cash->getKey())->set('observaciones', 'dato conservable');
            $change();
            $component->call('prepararPago')->assertSet('mostrarConfirmacion', false)
                ->assertSet('confirmacionFingerprint', null)->assertHasErrors()
                ->assertSet('observaciones', 'dato conservable');
            $this->assertDatabaseCount('pagos', 0);
            $this->assertDatabaseCount('consecutivos_pago', 0);
        }
    }

    public function test_full_review_edit_and_method_change_flow_has_no_financial_persistence(): void
    {
        Storage::fake('public');
        Notification::fake();
        $first = $this->cargo(['saldo_pendiente' => '40.40', 'total' => '40.40']);
        $second = $this->cargo(['saldo_pendiente' => '20.20', 'total' => '20.20']);
        $spei = MetodoPago::where('clave', MetodoPago::TRANSFERENCIA_SPEI)->firstOrFail();
        $cash = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $tables = collect(['pagos', 'consecutivos_pago', 'cargos', 'inscripciones', 'responsables_pago', 'metodos_pago'])
            ->merge(Schema::hasTable('pago_aplicaciones') ? ['pago_aplicaciones'] : [])
            ->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy(DB::raw('1'))->get()->map(fn ($row) => (array) $row)->all()]);

        $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarTodosCargos')
            ->set('importesAplicar.'.$first->getKey(), '30.30')->set('montoRecibido', '50.50')
            ->set('metodoPagoId', $spei->getKey())
            ->set('datosMetodo', ['banco' => 'Banco', 'referencia' => 'REF-FLUJO', 'rastreo_spei' => 'SPEI-FLUJO'])
            ->set('comprobante', UploadedFile::fake()->create('temporal.pdf', 20, 'application/pdf'))
            ->call('prepararPago')->assertSet('mostrarConfirmacion', true)
            ->call('volverAEditar')->set('observaciones', 'edición')
            ->set('metodoPagoId', $cash->getKey())->assertSet('comprobante', null)->assertSet('datosMetodo', [])
            ->call('prepararPago')->assertSet('mostrarConfirmacion', true);

        foreach ($tables as $table => $before) {
            $this->assertSame($before, DB::table($table)->orderBy(DB::raw('1'))->get()->map(fn ($row) => (array) $row)->all(), "El flujo modificó {$table}.");
        }
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('consecutivos_pago', 0);
        $this->assertSame('40.40', $first->fresh()->saldo_pendiente);
        $this->assertSame('20.20', $second->fresh()->saldo_pendiente);
        Storage::disk('public')->assertDirectoryEmpty('/');
        Notification::assertNothingSent();
    }

    public function test_each_applicable_persisted_identifier_produces_a_non_blocking_generic_warning(): void
    {
        $cargo = $this->cargo();
        $cases = [
            [MetodoPago::TRANSFERENCIA_SPEI, 'referencia', ['banco' => 'Banco', 'referencia' => ' REF-DUP ', 'rastreo_spei' => 'NUEVO-1']],
            [MetodoPago::TRANSFERENCIA_SPEI, 'rastreo_spei', ['banco' => 'Banco', 'referencia' => 'NUEVA-2', 'rastreo_spei' => ' SPEI-DUP ']],
            [MetodoPago::CHEQUE_NOMINATIVO, 'numero_cheque', ['banco' => 'Banco', 'numero_cheque' => ' CHQ-DUP ']],
            [MetodoPago::TARJETA_CREDITO, 'numero_autorizacion', ['numero_autorizacion' => ' AUT-DUP ', 'terminal' => 'T1', 'ultimos_4_digitos' => '1234']],
        ];
        foreach ($cases as $index => [$methodKey, $field, $data]) {
            $method = MetodoPago::where('clave', $methodKey)->firstOrFail();
            $stored = mb_strtoupper(trim($data[$field]), 'UTF-8');
            $payment = $this->existingPayment($method, ['folio' => 'PAG-2026-'.(900000 + $index), $field => $stored]);
            $component = Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
                ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
                ->set('metodoPagoId', $method->getKey())->set('datosMetodo', $data);
            if ($method->requiere_comprobante) {
                $component->set('comprobante', UploadedFile::fake()->create('identificador.pdf', 10, 'application/pdf'));
            }
            $component->call('prepararPago')->assertSee('identificador coincidente')->assertSet('mostrarConfirmacion', true)
                ->assertViewHas('advertenciaDuplicidad', fn ($warning) => $warning === 'Existe otro pago activo con una referencia o identificador coincidente. Verifica los datos antes de continuar.');
            $payment->delete();
        }
    }

    public function test_empty_applicable_identifiers_do_not_warn_or_query_other_payment_details(): void
    {
        $cargo = $this->cargo();
        $cash = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $this->existingPayment($cash, ['folio' => 'FOLIO-SENSIBLE', 'monto' => '987.65']);
        Livewire::actingAs($this->user('admin'))->test(RegistrarPago::class)
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())->call('seleccionarCargo', $cargo->getKey())
            ->set('metodoPagoId', $cash->getKey())->set('datosMetodo', ['referencia' => '   '])
            ->call('prepararPago')->assertSet('mostrarConfirmacion', true)->assertDontSee('identificador coincidente')
            ->assertDontSee('FOLIO-SENSIBLE')->assertDontSee('987.65');
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

    private function existingPayment(MetodoPago $metodo, array $overrides = []): Pago
    {
        return Pago::create(array_merge([
            'folio' => 'PAG-2026-999999',
            'inscripciones_id' => $this->inscripcion->getKey(),
            'prospectos_id' => $this->inscripcion->prospectos_id,
            'responsable_pago_id' => $this->inscripcion->responsable_pago_id,
            'fecha_pago' => '2026-09-09 11:00:00',
            'zona_horaria' => config('app.timezone'),
            'moneda' => 'MXN',
            'tipo_cambio' => '1.000000',
            'monto' => '100.00',
            'metodo_pago_id' => $metodo->getKey(),
            'forma_pago_sat' => $metodo->clave_forma_pago_sat,
        ], $overrides));
    }
}
