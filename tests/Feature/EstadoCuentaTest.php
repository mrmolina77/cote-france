<?php

namespace Tests\Feature;

use App\Http\Livewire\EstadoCuenta;
use App\Http\Livewire\RegistrarPago;
use App\Http\Livewire\ShowCobranza;
use App\Http\Livewire\ShowInscripciones;
use App\Http\Livewire\ShowPagos;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Inscripcion;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Prospecto;
use App\Models\ResponsablePago;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class EstadoCuentaTest extends InscripcionesTestCase
{
    private User $admin;
    private Inscripcion $inscripcion;
    private ResponsablePago $responsable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->user('admin');
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $prospecto->update(['prospectos_apellidos' => 'Cuenta', 'prospectos_correo' => 'alumno@example.test']);
        $this->responsable = ResponsablePago::create(['tipo' => 'persona', 'prospectos_id' => $prospecto->getKey(), 'nombre_razon_social' => 'Responsable Cuenta', 'telefono' => '5551', 'correo' => 'responsable@example.test', 'activo' => true]);
        $this->inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $this->inscripcion->forceFill(['estatus' => 'activa', 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2027-02-28', 'moneda' => 'MXN', 'monto_inscripcion' => '500.00', 'monto_mensualidad' => '1200.00', 'dia_vencimiento' => 10, 'numero_mensualidades' => 6, 'descuento' => '5.00', 'beca' => '10.00', 'observaciones_financieras' => 'Condición acordada', 'responsable_pago_id' => $this->responsable->getKey()])->save();
    }

    public function test_route_and_every_public_action_require_financial_authorization(): void
    {
        $this->get(route('facturacion.estado-cuenta'))->assertRedirect('/login');
        $venta = $this->user('venta');
        $this->actingAs($venta)->get(route('facturacion.estado-cuenta'))->assertOk();
        Livewire::actingAs($venta)->test(EstadoCuenta::class)
            ->set('busqueda', 'Alumno')
            ->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->call('limpiarSeleccion')
            ->call('limpiarBusqueda')
            ->assertOk();

        foreach (['profe', 'alum', 'otro'] as $rol) {
            $this->actingAs($this->user($rol))->get(route('facturacion.estado-cuenta'))->assertForbidden();
        }
        $sinRol = User::factory()->create(['roles_id' => 999999]);
        $this->actingAs($sinRol)->get(route('facturacion.estado-cuenta'))->assertForbidden();
    }

    public function test_admin_can_open_selector_and_valid_enrollment_while_invalid_id_is_404(): void
    {
        $this->actingAs($this->admin)->get(route('facturacion.estado-cuenta'))->assertOk()->assertSee('Seleccionar inscripción');
        $this->actingAs($this->admin)->get(route('facturacion.estado-cuenta', $this->inscripcion))->assertOk()->assertSee('Alumno Cuenta');
        $this->actingAs($this->admin)->get(route('facturacion.estado-cuenta', 999999))->assertNotFound()->assertDontSee('Alumno Cuenta');
    }

    public function test_conditions_null_fallbacks_and_empty_totals_are_visible(): void
    {
        Livewire::actingAs($this->admin)->test(EstadoCuenta::class, ['inscripcion' => $this->inscripcion->getKey()])
            ->assertSee('2026-09-01')->assertSee('2027-02-28')->assertSee('500.00')->assertSee('1,200.00')
            ->assertSee('Condición acordada')->assertSee('Responsable Cuenta')->assertSee('No hay cargos registrados')
            ->assertViewHas('resumen', ['total_cargos' => '0.00', 'total_pagado' => '0.00', 'saldo_pendiente' => '0.00', 'saldo_vencido' => '0.00']);
        $this->inscripcion->forceFill(['fecha_fin' => null, 'monto_inscripcion' => null, 'responsable_pago_id' => null])->save();
        Livewire::actingAs($this->admin)->test(EstadoCuenta::class, ['inscripcion' => $this->inscripcion->getKey()])->assertSee('Sin configurar')->assertSee('—');
    }

    public function test_totals_use_current_charge_balances_and_exclude_cancelled_charges(): void
    {
        $this->cargo('100.10', '40.05', Cargo::ESTADO_PARCIAL, 2026, 9);
        $this->cargo('80.00', '80.00', Cargo::ESTADO_VENCIDO, 2026, 10);
        $this->cargo('999.00', '0.00', Cargo::ESTADO_CANCELADO, 2026, 11);
        $this->cargo('12.34', '20.00', Cargo::ESTADO_PARCIAL, null, null, null);

        Livewire::actingAs($this->admin)->test(EstadoCuenta::class, ['inscripcion' => $this->inscripcion->getKey()])
            ->assertViewHas('resumen', ['total_cargos' => '192.44', 'total_pagado' => '60.05', 'saldo_pendiente' => '140.05', 'saldo_vencido' => '80.00'])
            ->assertSeeInOrder(['Periodo 2026-09', 'Periodo 2026-10', 'Periodo Sin periodo'])
            ->assertSee('Mensualidad')->assertSee('Cancelado');
    }

    public function test_payment_history_has_all_states_order_applications_and_is_isolated(): void
    {
        $cargo = $this->cargo('100.00', '100.00', Cargo::ESTADO_PENDIENTE, 2026, 9);
        foreach ([Pago::ESTADO_CONFIRMADO, Pago::ESTADO_BORRADOR, Pago::ESTADO_CANCELADO, Pago::ESTADO_REEMBOLSADO] as $index => $estado) {
            $pago = $this->pago('FOLIO-'.$estado, $estado, sprintf('2026-09-%02d 10:00:00', 10 + $index));
            if ($estado === Pago::ESTADO_CANCELADO) {
                $pago->forceFill(['motivo_cancelacion' => 'Pago duplicado', 'fecha_cancelacion' => '2026-09-20 10:00:00'])->save();
                DB::table('pago_aplicaciones')->insert(['pago_id' => $pago->getKey(), 'cargo_id' => $cargo->getKey(), 'importe_aplicado' => '30.00', 'saldo_anterior' => '100.00', 'saldo_posterior' => '70.00', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $otra = $this->enroll($prospecto, $curso, $grupo);
        $this->pago('AJENO-SECRETO', Pago::ESTADO_CONFIRMADO, '2026-09-30 10:00:00', $otra);

        Livewire::actingAs($this->admin)->test(EstadoCuenta::class, ['inscripcion' => $this->inscripcion->getKey()])
            ->assertSeeInOrder(['FOLIO-reembolsado', 'FOLIO-cancelado', 'FOLIO-borrador', 'FOLIO-confirmado'])
            ->assertSee('Pago duplicado')->assertSee('30.00')->assertDontSee('AJENO-SECRETO')
            ->assertViewHas('resumen', fn ($resumen) => $resumen['total_pagado'] === '0.00');
    }

    public function test_safe_search_links_and_read_only_actions(): void
    {
        $cargo = $this->cargo('88.00', '22.00', Cargo::ESTADO_PARCIAL, 2026, 9);
        $pago = $this->pago('INTOCABLE', Pago::ESTADO_CONFIRMADO, '2026-09-10 10:00:00');
        $before = [$this->inscripcion->fresh()->getAttributes(), $cargo->fresh()->getAttributes(), $pago->fresh()->getAttributes(), DB::table('pago_aplicaciones')->count()];

        Livewire::actingAs($this->admin)->test(EstadoCuenta::class)->set('busqueda', "%_' OR 1=1 --")->assertDontSee('Alumno Cuenta')->call('limpiarBusqueda')
            ->set('busqueda', 'Alumno')->assertSee('Alumno Cuenta')->call('seleccionarInscripcion', $this->inscripcion->getKey())
            ->assertSee(route('facturacion.pagos.registrar', $this->inscripcion->getKey()), false)->call('limpiarSeleccion');

        $this->assertSame($before, [$this->inscripcion->fresh()->getAttributes(), $cargo->fresh()->getAttributes(), $pago->fresh()->getAttributes(), DB::table('pago_aplicaciones')->count()]);
    }

    /** @dataProvider financialMenuViews */
    public function test_real_financial_menus_show_statement_link_to_read_roles(string $view): void
    {
        $url = route('facturacion.estado-cuenta');
        $this->actingAs($this->admin);
        $html = view($view)->render();
        $this->assertStringContainsString('Facturación y pagos', $html);
        $this->assertStringContainsString('Estado de cuenta', $html);
        $this->assertStringContainsString($url, $html);

        foreach (['caja', 'contabilidad', 'venta'] as $rol) {
            $this->actingAs($this->user($rol));
            $html = view($view)->render();
            $this->assertStringContainsString($url, $html);
            $this->assertStringContainsString('Facturación y pagos', $html);
        }

        foreach (['profe', 'alum', 'otro'] as $rol) {
            $this->actingAs($this->user($rol));
            $html = view($view)->render();
            $this->assertStringNotContainsString($url, $html);
            $this->assertStringNotContainsString('Facturación y pagos', $html);
        }

        $this->actingAs(User::factory()->create(['roles_id' => 999999]));
        $html = view($view)->render();
        $this->assertStringNotContainsString($url, $html);
        $this->assertStringNotContainsString('Facturación y pagos', $html);
    }

    public function financialMenuViews(): array
    {
        return [['components.layout.aside'], ['components.layout.mobile-header']];
    }

    public function test_enrollment_navigation_links_keep_the_selected_enrollment_id(): void
    {
        [$otroProspecto, $curso, $grupo] = $this->catalogs();
        $otra = $this->enroll($otroProspecto, $curso, $grupo);
        $pago = $this->pago('ENLACE-PRIMERO', Pago::ESTADO_CONFIRMADO, '2026-09-10 10:00:00');
        $estadoPrimera = route('facturacion.estado-cuenta', $this->inscripcion->getKey());
        $estadoSegunda = route('facturacion.estado-cuenta', $otra->getKey());
        $registrarPrimera = route('facturacion.pagos.registrar', $this->inscripcion->getKey());

        Livewire::actingAs($this->admin)->test(ShowInscripciones::class)->call('loadPosts')
            ->assertSee($estadoPrimera, false)->assertSee($estadoSegunda, false);
        Livewire::actingAs($this->admin)->test(ShowCobranza::class)
            ->assertSee($pago->folio)->assertSee($estadoPrimera, false)->assertDontSee($estadoSegunda, false);
        Livewire::actingAs($this->admin)->test(ShowPagos::class)
            ->assertSee($pago->folio)->assertSee($estadoPrimera, false)->assertDontSee($estadoSegunda, false);
        Livewire::actingAs($this->admin)->test(RegistrarPago::class, ['inscripcion' => $this->inscripcion->getKey()])
            ->assertSee($estadoPrimera, false)->assertDontSee($estadoSegunda, false);
        Livewire::actingAs($this->admin)->test(EstadoCuenta::class, ['inscripcion' => $this->inscripcion->getKey()])
            ->assertSee($registrarPrimera, false)
            ->assertDontSee(route('facturacion.pagos.registrar', $otra->getKey()), false);
    }

    /** @dataProvider inconsistentBalances */
    public function test_inconsistent_charge_balances_are_normalized_only_for_presentation(string $storedBalance, string $expectedPaid, string $expectedBalance): void
    {
        $cargo = $this->cargo('100.00', $storedBalance, Cargo::ESTADO_PARCIAL, 2026, 9);

        Livewire::actingAs($this->admin)->test(EstadoCuenta::class, ['inscripcion' => $this->inscripcion->getKey()])
            ->assertSee('MXN $100.00')
            ->assertSee('MXN $'.$expectedPaid)
            ->assertSee('MXN $'.$expectedBalance)
            ->assertDontSee('<td class="border-b p-3">MXN $'.$storedBalance.'</td>', false)
            ->assertDontSee('<td class="border-b p-3">MXN $150.00</td>', false);

        $this->assertSame($storedBalance, $cargo->fresh()->saldo_pendiente);
        $this->assertSame('100.00', $cargo->fresh()->total);
    }

    public function inconsistentBalances(): array
    {
        return [
            'negative balance' => ['-25.00', '100.00', '0.00'],
            'balance above total' => ['150.00', '0.00', '100.00'],
        ];
    }

    public function test_statement_excludes_every_charge_and_payment_field_from_another_enrollment(): void
    {
        $propio = $this->cargo('25.00', '25.00', Cargo::ESTADO_PENDIENTE, 2026, 9);
        $propio->forceFill(['observaciones' => 'OBSERVACION-PROPIA'])->save();
        [$otroProspecto, $curso, $grupo] = $this->catalogs();
        $otra = $this->enroll($otroProspecto, $curso, $grupo);
        $conceptoAjeno = ConceptoCobro::create(['clave' => 'CONCEPTO-AJENO', 'nombre' => 'Concepto exclusivo ajeno', 'activo' => true]);
        Cargo::create([
            'inscripciones_id' => $otra->getKey(), 'concepto_cobro_id' => $conceptoAjeno->getKey(),
            'periodo_anio' => 2042, 'periodo_mes' => 12, 'fecha_emision' => '2042-12-01',
            'fecha_vencimiento' => '2042-12-10', 'moneda' => 'MXN', 'subtotal' => '777.00',
            'descuento' => '0.00', 'recargo' => '0.00', 'impuestos' => '0.00', 'total' => '777.00',
            'saldo_pendiente' => '777.00', 'estado' => Cargo::ESTADO_PENDIENTE, 'origen' => 'manual',
            'observaciones' => 'OBSERVACION-AJENA-EXCLUSIVA',
        ]);
        $this->pago('FOLIO-AJENO-EXCLUSIVO', Pago::ESTADO_CONFIRMADO, '2042-12-11 10:00:00', $otra);

        Livewire::actingAs($this->admin)->test(EstadoCuenta::class, ['inscripcion' => $this->inscripcion->getKey()])
            ->assertSee('OBSERVACION-PROPIA')
            ->assertDontSee('CONCEPTO-AJENO')->assertDontSee('Concepto exclusivo ajeno')
            ->assertDontSee('OBSERVACION-AJENA-EXCLUSIVA')->assertDontSee('Periodo 2042-12')
            ->assertDontSee('FOLIO-AJENO-EXCLUSIVO');
    }

    private function cargo(string $total, string $saldo, string $estado, ?int $anio, ?int $mes, ?string $vencimiento = '2026-09-10'): Cargo
    {
        return Cargo::create(['inscripciones_id' => $this->inscripcion->getKey(), 'concepto_cobro_id' => ConceptoCobro::where('clave', 'MENSUALIDAD')->value('concepto_cobro_id'), 'periodo_anio' => $anio, 'periodo_mes' => $mes, 'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => $vencimiento ?? '2026-09-10', 'moneda' => 'MXN', 'subtotal' => $total, 'descuento' => '0.00', 'recargo' => '0.00', 'impuestos' => '0.00', 'total' => $total, 'saldo_pendiente' => $saldo, 'estado' => $estado, 'origen' => 'manual']);
    }

    private function pago(string $folio, string $estado, string $fecha, ?Inscripcion $inscripcion = null): Pago
    {
        $inscripcion ??= $this->inscripcion;
        return Pago::query()->forceCreate(['folio' => $folio, 'inscripciones_id' => $inscripcion->getKey(), 'prospectos_id' => $inscripcion->prospectos_id, 'responsable_pago_id' => $inscripcion->responsable_pago_id ?? $this->responsable->getKey(), 'fecha_pago' => $fecha, 'zona_horaria' => 'UTC', 'moneda' => 'MXN', 'monto' => '30.00', 'metodo_pago_id' => MetodoPago::value('metodo_pago_id'), 'estado' => $estado]);
    }
}
