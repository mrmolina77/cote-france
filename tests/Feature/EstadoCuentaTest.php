<?php

namespace Tests\Feature;

use App\Http\Livewire\EstadoCuenta;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Inscripcion;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Prospecto;
use App\Models\ResponsablePago;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
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
        foreach (['venta', 'profe', 'alum', 'otro'] as $rol) {
            $this->actingAs($this->user($rol))->get(route('facturacion.estado-cuenta'))->assertForbidden();
        }
        $sinRol = User::factory()->create(['roles_id' => 999999]);
        $this->actingAs($sinRol)->get(route('facturacion.estado-cuenta'))->assertForbidden();
        Livewire::actingAs($this->user('venta'))->test(EstadoCuenta::class)->assertForbidden();

        $this->actingAs($this->user('venta'));
        $this->expectException(AuthorizationException::class);
        (new EstadoCuenta())->limpiarBusqueda();
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

    private function cargo(string $total, string $saldo, string $estado, ?int $anio, ?int $mes, ?string $vencimiento = '2026-09-10'): Cargo
    {
        return Cargo::create(['inscripciones_id' => $this->inscripcion->getKey(), 'concepto_cobro_id' => ConceptoCobro::where('clave', 'MENSUALIDAD')->value('concepto_cobro_id'), 'periodo_anio' => $anio, 'periodo_mes' => $mes, 'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => $vencimiento, 'moneda' => 'MXN', 'subtotal' => $total, 'descuento' => '0.00', 'recargo' => '0.00', 'impuestos' => '0.00', 'total' => $total, 'saldo_pendiente' => $saldo, 'estado' => $estado, 'origen' => 'manual']);
    }

    private function pago(string $folio, string $estado, string $fecha, ?Inscripcion $inscripcion = null): Pago
    {
        $inscripcion ??= $this->inscripcion;
        return Pago::query()->forceCreate(['folio' => $folio, 'inscripciones_id' => $inscripcion->getKey(), 'prospectos_id' => $inscripcion->prospectos_id, 'responsable_pago_id' => $inscripcion->responsable_pago_id, 'fecha_pago' => $fecha, 'zona_horaria' => 'UTC', 'moneda' => 'MXN', 'monto' => '30.00', 'metodo_pago_id' => MetodoPago::value('metodo_pago_id'), 'estado' => $estado]);
    }
}
