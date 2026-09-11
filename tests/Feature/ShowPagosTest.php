<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowPagos;
use App\Models\Cargo;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\ResponsablePago;
use App\Models\User;
use App\Services\Facturacion\AplicarPagoService;
use App\Services\Facturacion\CancelarPagoService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use ReflectionClass;

class ShowPagosTest extends InscripcionesTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_access_and_gates_are_restricted_to_admins(): void
    {
        $this->get('/facturacion/pagos')->assertRedirect('/login');
        $admin = $this->user('admin');
        $this->actingAs($admin)->get('/facturacion/pagos')->assertOk();
        $this->assertTrue(Gate::forUser($admin)->allows('manage-pagos'));
        $this->assertTrue(Gate::forUser($admin)->allows('cancel-pagos'));

        foreach (['venta', 'profe'] as $rol) {
            $usuario = $this->user($rol);
            $this->assertFalse(Gate::forUser($usuario)->allows('manage-pagos'));
            $this->assertFalse(Gate::forUser($usuario)->allows('cancel-pagos'));
            $this->actingAs($usuario)->get('/facturacion/pagos')->assertForbidden();
            Livewire::actingAs($usuario)->test(ShowPagos::class)->assertForbidden();
        }
        $sinRol = User::factory()->create(['roles_id' => 999999]);
        $this->actingAs($sinRol)->get('/facturacion/pagos')->assertForbidden();
    }

    /** @dataProvider sensitiveActions */
    public function test_sensitive_actions_reauthorize_when_called_directly(string $method): void
    {
        $usuario = $this->user('venta');
        $this->actingAs($usuario);
        $component = new ShowPagos();
        $this->expectException(AuthorizationException::class);
        $method === 'prepararCancelacion' ? $component->{$method}(1) : app()->call([$component, $method]);
    }

    public function sensitiveActions(): array
    {
        return [['prepararCancelacion'], ['confirmarCancelacion'], ['cerrarCancelacion']];
    }

    public function test_non_admin_cannot_see_cancel_button(): void
    {
        $admin = $this->user('admin');
        $pago = $this->pago($admin, ['folio' => 'VISIBLE-SOLO-ADMIN']);
        $venta = $this->user('venta');
        $this->assertFalse(Gate::forUser($venta)->allows('cancel-pagos'));
        $html = view('livewire.show-pagos', [
            'pagos' => Pago::paginate(10), 'metodos' => MetodoPago::all(), 'detalle' => null, 'pagoCancelar' => null,
        ])->with('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag()))->render();
        $this->assertStringContainsString($pago->folio, $html);
        $this->assertStringNotContainsString('>Cancelar</button>', $html);
    }

    /** @dataProvider searchCases */
    public function test_searches_each_supported_field(string $field, string $needle): void
    {
        $admin = $this->user('admin');
        $match = $this->pago($admin, ['folio' => 'MATCH-'.$field]);
        $other = $this->pago($admin, ['folio' => 'OTHER-'.$field]);
        if ($field === 'inscripcion') $needle = (string) $match->inscripciones_id;
        elseif (in_array($field, ['nombre', 'apellido'], true)) {
            $match->prospecto->update([$field === 'nombre' ? 'prospectos_nombres' : 'prospectos_apellidos' => $needle]);
        } else $match->forceFill([$field => $needle])->save();

        Livewire::actingAs($admin)->test(ShowPagos::class)->set('busqueda', $needle)
            ->assertSee($match->folio)->assertDontSee($other->folio);
    }

    public function searchCases(): array
    {
        return [
            'folio' => ['folio', 'FOLIO-UNICO'], 'inscripción' => ['inscripcion', ''],
            'nombre' => ['nombre', 'ÉlodieUnica'], 'apellido' => ['apellido', 'MontmartreUnico'],
            'referencia' => ['referencia', 'REF-UNICA'], 'SPEI' => ['rastreo_spei', 'SPEI-UNICO'],
        ];
    }

    public function test_no_match_and_sql_like_input_do_not_broaden_results(): void
    {
        $admin = $this->user('admin');
        $one = $this->pago($admin, ['folio' => 'PAGO-UNO']);
        $two = $this->pago($admin, ['folio' => 'PAGO-DOS']);
        Livewire::actingAs($admin)->test(ShowPagos::class)->set('busqueda', 'NO-EXISTE')
            ->assertDontSee($one->folio)->assertDontSee($two->folio)->assertSee('No se encontraron pagos')
            ->set('busqueda', "%' OR 1=1 --")->assertDontSee($one->folio)->assertDontSee($two->folio);
    }

    /** @dataProvider states */
    public function test_filters_each_state_against_a_different_state(string $state): void
    {
        $admin = $this->user('admin');
        $match = $this->pago($admin, ['folio' => 'MATCH-'.$state, 'estado' => $state]);
        $different = $state === Pago::ESTADO_CONFIRMADO ? Pago::ESTADO_BORRADOR : Pago::ESTADO_CONFIRMADO;
        $other = $this->pago($admin, ['folio' => 'OTHER-'.$state, 'estado' => $different]);
        Livewire::actingAs($admin)->test(ShowPagos::class)->set('estado', $state)
            ->assertSee($match->folio)->assertDontSee($other->folio);
    }

    public function states(): array
    {
        return array_map(fn ($state) => [$state], Pago::ESTADOS);
    }

    public function test_method_filter_manipulated_filters_and_stable_sorting(): void
    {
        $admin = $this->user('admin');
        $methods = MetodoPago::take(2)->get();
        $older = $this->pago($admin, ['folio' => 'OLDER', 'metodo_pago_id' => $methods[0]->getKey(), 'fecha_pago' => '2026-09-01 10:00:00']);
        $tieLow = $this->pago($admin, ['folio' => 'TIE-LOW', 'metodo_pago_id' => $methods[0]->getKey(), 'fecha_pago' => '2026-09-10 10:00:00']);
        $tieHigh = $this->pago($admin, ['folio' => 'TIE-HIGH', 'metodo_pago_id' => $methods[1]->getKey(), 'fecha_pago' => '2026-09-10 10:00:00']);
        Livewire::actingAs($admin)->test(ShowPagos::class)
            ->assertSeeInOrder([$tieHigh->folio, $tieLow->folio, $older->folio])
            ->set('metodoPagoId', (string) $methods[0]->getKey())->assertSee($tieLow->folio)->assertDontSee($tieHigh->folio)
            ->set('metodoPagoId', "1 OR 1=1")->assertSee($tieHigh->folio)
            ->set('estado', "confirmado' OR 1=1 --")->assertSee($older->folio)->assertSee($tieHigh->folio);
    }

    /** @dataProvider dateRanges */
    public function test_date_ranges_really_filter_results(string $from, string $to, bool $oldVisible, bool $newVisible): void
    {
        $admin = $this->user('admin');
        $old = $this->pago($admin, ['folio' => 'OLD-DATE', 'fecha_pago' => '2026-09-01 12:00:00']);
        $new = $this->pago($admin, ['folio' => 'NEW-DATE', 'fecha_pago' => '2026-09-10 12:00:00']);
        $component = Livewire::actingAs($admin)->test(ShowPagos::class)->set('fechaDesde', $from)->set('fechaHasta', $to);
        ($oldVisible ? $component->assertSee($old->folio) : $component->assertDontSee($old->folio));
        ($newVisible ? $component->assertSee($new->folio) : $component->assertDontSee($new->folio));
    }

    public function dateRanges(): array
    {
        return [
            'desde' => ['2026-09-05', '', false, true], 'hasta' => ['', '2026-09-05', true, false],
            'completo' => ['2026-09-05', '2026-09-11', false, true], 'vacío' => ['', '', true, true],
            'invertido no parcial' => ['2026-09-11', '2026-09-05', true, true],
        ];
    }

    /** @dataProvider invalidDates */
    public function test_impossible_dates_are_safe(string $field, string $date): void
    {
        $admin = $this->user('admin');
        $pago = $this->pago($admin, ['folio' => 'STILL-LISTED']);
        Livewire::actingAs($admin)->test(ShowPagos::class)->set($field, $date)
            ->assertHasErrors($field)->assertSee($pago->folio);
    }

    public function invalidDates(): array
    {
        return [['fechaDesde', '2026-02-30'], ['fechaHasta', '2026-13-01'], ['fechaDesde', '2026-04-31'], ['fechaHasta', 'mañana']];
    }

    public function test_pagination_whitelist_and_every_filter_resets_page(): void
    {
        $admin = $this->user('admin');
        for ($i = 0; $i < 12; $i++) $this->pago($admin, ['folio' => sprintf('PAGE-%02d', $i)]);
        foreach ([10, 25, 50] as $size) {
            Livewire::actingAs($admin)->test(ShowPagos::class)->set('porPagina', $size)->assertSet('porPagina', $size);
        }
        Livewire::actingAs($admin)->test(ShowPagos::class)->set('porPagina', 999)->assertSet('porPagina', 10);
        foreach (['busqueda' => 'x', 'estado' => Pago::ESTADO_CONFIRMADO, 'metodoPagoId' => 'todos', 'fechaDesde' => '2026-01-01', 'fechaHasta' => '2026-12-31'] as $field => $value) {
            Livewire::actingAs($admin)->test(ShowPagos::class)->call('gotoPage', 2)->set($field, $value)->assertSet('page', 1);
        }
    }

    public function test_detail_rejects_bad_ids_and_closes_cleanly(): void
    {
        $admin = $this->user('admin');
        $pago = $this->pago($admin);
        Livewire::actingAs($admin)->test(ShowPagos::class)->call('verDetalle', $pago->getKey())
            ->assertSet('pagoDetalleId', $pago->getKey())->assertSet('mostrarModalDetalle', true)
            ->call('cerrarDetalle')->assertSet('pagoDetalleId', null)->assertSet('mostrarModalDetalle', false);
        Livewire::actingAs($admin)->test(ShowPagos::class)->call('verDetalle', 999999)->assertNotFound();
        Livewire::actingAs($admin)->test(ShowPagos::class)->call('verDetalle', 'abc')->assertNotFound();
    }

    public function test_detail_displays_payment_people_and_real_applications(): void
    {
        $admin = $this->user('admin');
        ['pago' => $pago, 'cargos' => $cargos] = $this->crearPagoConfirmadoConAplicaciones($admin);
        Livewire::actingAs($admin)->test(ShowPagos::class)->call('verDetalle', $pago->getKey())
            ->assertSee($pago->folio)->assertSee('Alumno Prueba')->assertSee('#'.$pago->inscripciones_id)
            ->assertSee('Responsable Integración')->assertSee($pago->metodoPago->nombre)->assertSee('$15.00')
            ->assertSee($admin->name)->assertSee('#'.$cargos[0]->getKey())->assertSee('Inscripción')
            ->assertSee('$5.00')->assertSee('$20.00')->assertSee('$15.00')
            ->assertDontSee('Cancelación</strong>', false);
    }

    public function test_cancelled_detail_shows_cancellation_audit(): void
    {
        $admin = $this->user('admin');
        ['pago' => $pago] = $this->crearPagoConfirmadoConAplicaciones($admin);
        app(CancelarPagoService::class)->cancelar($pago->getKey(), 'Error bancario', $admin->getKey());
        Livewire::actingAs($admin)->test(ShowPagos::class)->call('verDetalle', $pago->getKey())
            ->assertSee('Cancelación')->assertSee('Error bancario')->assertSee($admin->name)->assertSee(now()->format('Y-m-d'));
    }

    public function test_blade_escapes_controlled_payment_cancellation_and_flash_html(): void
    {
        $admin = $this->user('admin');
        $xss = '<script>alert(1)</script>';
        $pago = $this->pago($admin, ['folio' => $xss, 'referencia' => $xss, 'observaciones' => $xss, 'estado' => Pago::ESTADO_CANCELADO]);
        DB::table('pagos')->where('pago_id', $pago->getKey())->update(['motivo_cancelacion' => $xss, 'cancelled_by' => $admin->getKey(), 'fecha_cancelacion' => now()]);
        session()->flash('status', $xss);
        $html = Livewire::actingAs($admin)->test(ShowPagos::class)->call('verDetalle', $pago->getKey())->html();
        $this->assertStringNotContainsString($xss, $html);
        $this->assertGreaterThanOrEqual(5, substr_count($html, '&lt;script&gt;alert(1)&lt;/script&gt;'));
    }

    /** @dataProvider rejectedCancellationStates */
    public function test_modal_rejects_non_confirmed_states(string $state): void
    {
        $admin = $this->user('admin');
        $pago = $this->pago($admin, ['estado' => $state]);
        Livewire::actingAs($admin)->test(ShowPagos::class)->call('prepararCancelacion', $pago->getKey())
            ->assertHasErrors('pagoCancelarId')->assertSet('mostrarModalCancelacion', false);
    }

    public function rejectedCancellationStates(): array
    {
        return [[Pago::ESTADO_BORRADOR], [Pago::ESTADO_CANCELADO], [Pago::ESTADO_REEMBOLSADO]];
    }

    public function test_modal_accepts_confirmed_rejects_bad_ids_and_reselection_resets_state(): void
    {
        $admin = $this->user('admin');
        $a = $this->pago($admin); $b = $this->pago($admin);
        $component = Livewire::actingAs($admin)->test(ShowPagos::class)->call('prepararCancelacion', $a->getKey());
        $tokenA = $component->get('cancelacionToken');
        $component->set('motivoCancelacion', 'anterior')->addError('motivoCancelacion', 'error')
            ->call('prepararCancelacion', $b->getKey())->assertSet('pagoCancelarId', $b->getKey())
            ->assertSet('motivoCancelacion', '')->assertHasNoErrors()->assertSet('mostrarModalCancelacion', true);
        $this->assertNotSame($tokenA, $component->get('cancelacionToken'));
        foreach (['abc', 999999] as $id) Livewire::actingAs($admin)->test(ShowPagos::class)->call('prepararCancelacion', $id)->assertHasErrors('pagoCancelarId');
    }

    public function test_close_clears_all_modal_error_and_session_state(): void
    {
        $admin = $this->user('admin'); $pago = $this->pago($admin);
        $component = Livewire::actingAs($admin)->test(ShowPagos::class)->call('prepararCancelacion', $pago->getKey())
            ->set('motivoCancelacion', 'x')->addError('motivoCancelacion', 'error')->call('cerrarCancelacion')
            ->assertSet('pagoCancelarId', null)->assertSet('motivoCancelacion', '')->assertSet('cancelacionToken', null)
            ->assertSet('mostrarModalCancelacion', false)->assertHasNoErrors();
        $this->assertFalse(session()->has('show-pagos.cancelacion-token.'.$admin->getKey()));
    }

    /** @dataProvider invalidReasons */
    public function test_invalid_reasons_do_not_change_real_financial_records($reason): void
    {
        $admin = $this->user('admin');
        ['pago' => $pago, 'cargos' => $cargos, 'applications' => $apps] = $this->crearPagoConfirmadoConAplicaciones($admin);
        Livewire::actingAs($admin)->test(ShowPagos::class)->call('prepararCancelacion', $pago->getKey())
            ->set('motivoCancelacion', $reason)->call('confirmarCancelacion')->assertHasErrors('motivoCancelacion');
        $this->assertSame(Pago::ESTADO_CONFIRMADO, $pago->fresh()->estado);
        $this->assertSame(['15.00', '0.00'], $cargos->map(fn ($c) => $c->fresh()->saldo_pendiente)->all());
        $this->assertEquals($apps, DB::table('pago_aplicaciones')->where('pago_id', $pago->getKey())->orderBy('pago_aplicacion_id')->get()->toArray());
    }

    public function invalidReasons(): array
    {
        return [[''], ['   '], [['no-textual']], [str_repeat('x', 2001)]];
    }

    public function test_reason_boundary_and_outer_spaces_are_normalized(): void
    {
        $admin = $this->user('admin');
        ['pago' => $pago] = $this->crearPagoConfirmadoConAplicaciones($admin);
        Livewire::actingAs($admin)->test(ShowPagos::class)->call('prepararCancelacion', $pago->getKey())
            ->set('motivoCancelacion', '  '.str_repeat('x', 2000).'  ')->call('confirmarCancelacion')->assertHasNoErrors();
        $this->assertSame(str_repeat('x', 2000), $pago->fresh()->motivo_cancelacion);
    }

    public function test_tokens_reject_substitution_other_user_other_payment_and_tampering(): void
    {
        $admin = $this->user('admin'); $otherAdmin = $this->user('admin');
        $a = $this->pago($admin); $b = $this->pago($admin);
        $component = Livewire::actingAs($admin)->test(ShowPagos::class)->call('prepararCancelacion', $a->getKey());
        $token = $component->get('cancelacionToken');
        foreach ([
            [$b->getKey(), $token], [$a->getKey(), ''], [$a->getKey(), $token.'x'], [$a->getKey(), preg_replace('/.$/', '0', $token)],
        ] as [$id, $badToken]) {
            $component->set('pagoCancelarId', $id)->set('cancelacionToken', $badToken)->set('motivoCancelacion', 'Motivo válido')
                ->call('confirmarCancelacion')->assertHasErrors('pagoCancelarId');
        }
        Livewire::actingAs($otherAdmin)->test(ShowPagos::class)->set('pagoCancelarId', $a->getKey())
            ->set('cancelacionToken', $token)->set('mostrarModalCancelacion', true)->set('motivoCancelacion', 'Motivo válido')
            ->call('confirmarCancelacion')->assertHasErrors('pagoCancelarId');
        $this->assertSame(Pago::ESTADO_CONFIRMADO, $a->fresh()->estado);
        $this->assertSame(Pago::ESTADO_CONFIRMADO, $b->fresh()->estado);
    }

    public function test_token_expires_without_sleep_and_test_clock_is_restored(): void
    {
        $admin = $this->user('admin'); $pago = $this->pago($admin);
        Carbon::setTestNow('2026-09-11 10:00:00');
        try {
            $component = Livewire::actingAs($admin)->test(ShowPagos::class)->call('prepararCancelacion', $pago->getKey());
            Carbon::setTestNow('2026-09-11 10:30:01');
            $component->set('motivoCancelacion', 'Motivo válido')->call('confirmarCancelacion')->assertHasErrors('pagoCancelarId');
            $this->assertSame(Pago::ESTADO_CONFIRMADO, $pago->fresh()->estado);
        } finally { Carbon::setTestNow(); }
    }

    public function test_real_livewire_cancellation_restores_balances_and_preserves_audit_and_applications(): void
    {
        Carbon::setTestNow('2026-09-11 12:00:00');
        $admin = $this->user('admin');
        ['pago' => $pago, 'cargos' => $cargos, 'applications' => $apps] = $this->crearPagoConfirmadoConAplicaciones($admin);
        $original = $pago->only(['folio', 'confirmed_by']); $confirmedAt = $pago->fecha_confirmacion->toDateTimeString();
        $component = Livewire::actingAs($admin)->test(ShowPagos::class)->call('prepararCancelacion', $pago->getKey())
            ->set('motivoCancelacion', '  Error de captura  ')->call('confirmarCancelacion')->assertHasNoErrors()
            ->assertSet('mostrarModalCancelacion', false)->assertSet('pagoCancelarId', null)
            ->assertSet('motivoCancelacion', '')->assertSet('cancelacionToken', null)
            ->assertSee('El pago '.$pago->folio.' fue cancelado correctamente.')->assertDontSee('>Cancelar</button>', false);
        $fresh = $pago->fresh();
        $this->assertSame(Pago::ESTADO_CANCELADO, $fresh->estado);
        $this->assertSame($admin->getKey(), (int) $fresh->cancelled_by);
        $this->assertTrue($fresh->fecha_cancelacion->equalTo(now()));
        $this->assertSame('Error de captura', $fresh->motivo_cancelacion);
        $this->assertSame($original, $fresh->only(['folio', 'confirmed_by']));
        $this->assertSame($confirmedAt, $fresh->fecha_confirmacion->toDateTimeString());
        $this->assertDatabaseHas('pagos', ['pago_id' => $pago->getKey()]);
        $this->assertSame(['20.00', '10.00'], $cargos->map(fn ($c) => $c->fresh()->saldo_pendiente)->all());
        $this->assertSame([Cargo::ESTADO_PENDIENTE, Cargo::ESTADO_VENCIDO], $cargos->map(fn ($c) => $c->fresh()->estado)->all());
        $this->assertEquals($apps, DB::table('pago_aplicaciones')->where('pago_id', $pago->getKey())->orderBy('pago_aplicacion_id')->get()->toArray());
        $this->assertFalse(session()->has('show-pagos.cancelacion-token.'.$admin->getKey()));
        $component->set('pagoCancelarId', $pago->getKey())->set('mostrarModalCancelacion', true)
            ->set('motivoCancelacion', 'segunda')->call('confirmarCancelacion')->assertHasErrors('pagoCancelarId');
        $this->assertSame(['20.00', '10.00'], $cargos->map(fn ($c) => $c->fresh()->saldo_pendiente)->all());
        $this->assertDatabaseCount('pago_aplicaciones', count($apps));
    }

    public function test_stale_state_and_service_failure_leave_records_consistent(): void
    {
        $admin = $this->user('admin');
        ['pago' => $pago, 'cargos' => $cargos, 'applications' => $apps] = $this->crearPagoConfirmadoConAplicaciones($admin);
        $component = Livewire::actingAs($admin)->test(ShowPagos::class)->call('prepararCancelacion', $pago->getKey());
        DB::table('pagos')->where('pago_id', $pago->getKey())->update(['estado' => Pago::ESTADO_REEMBOLSADO]);
        $component->set('motivoCancelacion', 'Estado obsoleto')->call('confirmarCancelacion')
            ->assertHasErrors('pagoCancelarId')->assertDontSee('cancelado correctamente');
        $this->assertSame(Pago::ESTADO_REEMBOLSADO, $pago->fresh()->estado);
        $this->assertSame(['15.00', '0.00'], $cargos->map(fn ($c) => $c->fresh()->saldo_pendiente)->all());
        $this->assertEquals($apps, DB::table('pago_aplicaciones')->where('pago_id', $pago->getKey())->orderBy('pago_aplicacion_id')->get()->toArray());
        $this->assertStringNotContainsString('Stack trace', $component->html());
    }

    public function test_component_and_view_expose_no_payment_deletion(): void
    {
        $public = collect((new ReflectionClass(ShowPagos::class))->getMethods(\ReflectionMethod::IS_PUBLIC))->pluck('name')->map('strtolower');
        foreach (['delete', 'destroy', 'eliminar', 'forcedelete'] as $method) $this->assertFalse($public->contains($method));
        $view = file_get_contents(resource_path('views/livewire/show-pagos.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/wire:click=["\'][^"\']*(delete|destroy|eliminar|forceDelete)/i', $view);
        $this->assertStringNotContainsString('Eliminar pago', $view);
    }

    private function crearPagoConfirmadoConAplicaciones(User $admin): array
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $prospecto->update(['prospectos_nombres' => 'Alumno Prueba', 'prospectos_apellidos' => 'Integración']);
        $responsable = ResponsablePago::create(['tipo' => 'persona', 'prospectos_id' => $prospecto->getKey(), 'nombre_razon_social' => 'Responsable Integración', 'activo' => true]);
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $inscripcion->update(['estatus' => 'activa', 'moneda' => 'MXN', 'responsable_pago_id' => $responsable->getKey()]);
        $cargos = collect([
            Cargo::create(['inscripciones_id' => $inscripcion->getKey(), 'concepto_cobro_id' => 1, 'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-30', 'moneda' => 'MXN', 'subtotal' => '20.00', 'total' => '20.00', 'saldo_pendiente' => '20.00', 'estado' => Cargo::ESTADO_PENDIENTE, 'origen' => Cargo::ORIGEN_MANUAL]),
            Cargo::create(['inscripciones_id' => $inscripcion->getKey(), 'concepto_cobro_id' => 2, 'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-01', 'moneda' => 'MXN', 'subtotal' => '10.00', 'total' => '10.00', 'saldo_pendiente' => '10.00', 'estado' => Cargo::ESTADO_VENCIDO, 'origen' => Cargo::ORIGEN_MANUAL]),
        ]);
        $metodo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $pago = app(AplicarPagoService::class)->confirmar($inscripcion->getKey(), $metodo->getKey(), [
            'fecha_pago' => '2026-09-10 10:00:00', 'zona_horaria' => 'UTC', 'monto' => '15.00', 'observaciones' => 'Pago integrado',
        ], [$cargos[0]->getKey() => '5.00', $cargos[1]->getKey() => '10.00'], $admin->getKey())->load('metodoPago');

        return ['pago' => $pago, 'cargos' => $cargos, 'applications' => DB::table('pago_aplicaciones')->where('pago_id', $pago->getKey())->orderBy('pago_aplicacion_id')->get()->toArray()];
    }

    private function pago(User $usuario, array $changes = []): Pago
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $prospecto->update(['prospectos_apellidos' => 'Dupont']);
        $responsable = ResponsablePago::create(['tipo' => 'persona', 'prospectos_id' => $prospecto->getKey(), 'nombre_razon_social' => 'Responsable', 'activo' => true]);
        $inscripcion = $this->enroll($prospecto, $curso, $grupo); $inscripcion->update(['responsable_pago_id' => $responsable->getKey()]);
        $attributes = array_merge(['folio' => uniqid('PAG-'), 'inscripciones_id' => $inscripcion->getKey(), 'prospectos_id' => $prospecto->getKey(), 'responsable_pago_id' => $responsable->getKey(), 'fecha_pago' => '2026-09-09 12:00:00', 'zona_horaria' => 'UTC', 'monto' => '100.00', 'metodo_pago_id' => MetodoPago::query()->firstOrFail()->getKey()], $changes);
        $state = $attributes['estado'] ?? Pago::ESTADO_CONFIRMADO; unset($attributes['estado']);
        $pago = Pago::create($attributes);
        DB::table('pagos')->where('pago_id', $pago->getKey())->update(['estado' => $state, 'confirmed_by' => $usuario->getKey(), 'fecha_confirmacion' => '2026-09-09 12:00:00']);
        return $pago->fresh();
    }
}
