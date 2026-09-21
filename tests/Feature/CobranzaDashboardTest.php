<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowCobranza;
use App\Models\Cargo;
use App\Models\Inscripcion;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Prospecto;
use App\Models\ResponsablePago;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

class CobranzaDashboardTest extends InscripcionesTestCase
{
    private User $admin;
    private Inscripcion $inscripcion;
    private ResponsablePago $responsable;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-21 12:00:00');
        $this->admin = $this->user('admin');
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $prospecto->update(['prospectos_apellidos' => 'Principal']);
        $this->inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $this->inscripcion->forceFill(['estatus' => 'activa'])->save();
        $this->responsable = ResponsablePago::create([
            'tipo' => 'persona', 'prospectos_id' => $prospecto->getKey(),
            'nombre_razon_social' => 'Responsable', 'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_route_and_component_require_financial_authorization(): void
    {
        $this->get(route('facturacion.cobranza'))->assertRedirect('/login');
        $unauthorized = $this->user('venta');
        $this->actingAs($unauthorized)->get(route('facturacion.cobranza'))->assertForbidden();
        Livewire::actingAs($unauthorized)->test(ShowCobranza::class)->assertForbidden();
        $this->assertFalse(Gate::forUser($unauthorized)->allows('manage-cargos'));

        $this->actingAs($unauthorized);
        $this->expectException(AuthorizationException::class);
        (new ShowCobranza())->limpiarFiltros();
    }

    public function test_authorized_user_sees_dashboard_six_zero_cards_and_table(): void
    {
        $this->inscripcion->forceFill(['estatus' => 'cancelada'])->save();
        $response = $this->actingAs($this->admin)->get(route('facturacion.cobranza'));
        $response->assertOk()->assertSee('Cobranza')->assertSee('Movimientos de pago')
            ->assertSee('Estado de cuenta')
            ->assertSee('Cobrado este mes')->assertSee('Pendiente')->assertSee('Vencido')
            ->assertSee('Estudiantes activos')->assertSee('Pagos por confirmar')->assertSee('Pagos cancelados')
            ->assertSee('$0.00 MXN')->assertSee('No se encontraron movimientos');
    }

    public function test_kpis_use_exact_financial_definitions(): void
    {
        $this->pago(['folio' => 'CONF-MES', 'monto' => '125.40', 'estado' => Pago::ESTADO_CONFIRMADO]);
        $this->pago(['folio' => 'CONF-OTRO', 'monto' => '900.00', 'estado' => Pago::ESTADO_CONFIRMADO, 'fecha_pago' => '2026-08-31 23:59:59']);
        $this->pago(['folio' => 'BORRADOR', 'monto' => '50.00', 'estado' => Pago::ESTADO_BORRADOR]);
        $this->pago(['folio' => 'CANCELADO', 'monto' => '60.00', 'estado' => Pago::ESTADO_CANCELADO]);
        $this->pago(['folio' => 'REEMBOLSO', 'monto' => '70.00', 'estado' => Pago::ESTADO_REEMBOLSADO]);

        $this->cargo('10.10', Cargo::ESTADO_PENDIENTE);
        $this->cargo('20.20', Cargo::ESTADO_PARCIAL);
        $this->cargo('30.30', Cargo::ESTADO_VENCIDO);
        $this->cargo('40.40', Cargo::ESTADO_CANCELADO);
        $this->cargo('0.00', Cargo::ESTADO_VENCIDO);

        $second = Prospecto::create(['prospectos_nombres' => 'Segundo', 'prospectos_telefono1' => '5551111111']);
        $duplicate = $this->inscripcion->replicate();
        $duplicate->save();
        $other = $this->inscripcion->replicate();
        $other->prospectos_id = $second->getKey();
        $other->save();

        Livewire::actingAs($this->admin)->test(ShowCobranza::class)
            ->assertSee('$125.40 MXN')->assertSee('$60.60 MXN')->assertSee('$30.30 MXN')
            ->assertViewHas('kpis', fn ($kpis) => $kpis['estudiantes'] === 2 && $kpis['porConfirmar'] === 1 && $kpis['cancelados'] === 1);
    }

    /** @dataProvider searchFields */
    public function test_search_filters_each_supported_field(string $field, string $needle): void
    {
        $match = $this->pago(['folio' => 'MATCH-'.$field]);
        $other = $this->pago(['folio' => 'OTHER-'.$field]);
        if ($field === 'inscripcion') {
            $needle = (string) $match->inscripciones_id;
            $different = $this->inscripcion->replicate();
            $different->save();
            $other->forceFill(['inscripciones_id' => $different->getKey()])->save();
        } elseif ($field === 'nombre' || $field === 'apellido') {
            $otherProspecto = Prospecto::create([
                'prospectos_nombres' => 'OtroNombre',
                'prospectos_apellidos' => 'OtroApellido',
                'prospectos_telefono1' => '5559999999',
            ]);
            $otherInscripcion = $this->inscripcion->replicate();
            $otherInscripcion->prospectos_id = $otherProspecto->getKey();
            $otherInscripcion->save();
            $other->forceFill([
                'inscripciones_id' => $otherInscripcion->getKey(),
                'prospectos_id' => $otherProspecto->getKey(),
            ])->save();

            $match->prospecto->update([$field === 'nombre' ? 'prospectos_nombres' : 'prospectos_apellidos' => $needle]);
        } else {
            $match->forceFill([$field => $needle])->save();
        }

        Livewire::actingAs($this->admin)->test(ShowCobranza::class)->set('busqueda', $needle)
            ->assertSee($match->folio)->assertDontSee($other->folio);
    }

    public function searchFields(): array
    {
        return [
            'folio' => ['folio', 'FOLIO-UNICISIMO'], 'inscripcion' => ['inscripcion', ''],
            'nombre' => ['nombre', 'NombreUnicisimo'], 'apellido' => ['apellido', 'ApellidoUnicisimo'],
            'referencia' => ['referencia', 'REF-UNICISIMA'], 'spei' => ['rastreo_spei', 'SPEI-UNICISIMO'],
        ];
    }

    public function test_state_method_and_date_filters_separate_matches(): void
    {
        $methods = MetodoPago::query()->take(2)->get();
        $match = $this->pago(['folio' => 'COINCIDE', 'estado' => Pago::ESTADO_BORRADOR, 'metodo_pago_id' => $methods[0]->getKey(), 'fecha_pago' => '2026-09-10 10:00:00']);
        $other = $this->pago(['folio' => 'NO-COINCIDE', 'estado' => Pago::ESTADO_CONFIRMADO, 'metodo_pago_id' => $methods[1]->getKey(), 'fecha_pago' => '2026-09-01 10:00:00']);

        Livewire::actingAs($this->admin)->test(ShowCobranza::class)
            ->set('estado', Pago::ESTADO_BORRADOR)->set('metodoPagoId', (string) $methods[0]->getKey())
            ->set('fechaDesde', '2026-09-05')->set('fechaHasta', '2026-09-15')
            ->assertSee($match->folio)->assertDontSee($other->folio);
    }

    public function test_manipulated_filters_dates_and_literal_wildcards_are_safe(): void
    {
        $one = $this->pago(['folio' => 'NORMAL-UNO']);
        $two = $this->pago(['folio' => 'NORMAL-DOS']);
        $component = Livewire::actingAs($this->admin)->test(ShowCobranza::class)
            ->set('busqueda', "%_' OR 1=1 --")
            ->assertDontSee($one->folio)->assertDontSee($two->folio)
            ->set('busqueda', '')
            ->set('estado', ['confirmado'])->set('metodoPagoId', ['1'])->set('porPagina', 999)
            ->assertSet('porPagina', 10)
            ->set('fechaDesde', '2026-02-30')->assertHasErrors('fechaDesde')
            ->set('fechaDesde', '2026-09-20')->set('fechaHasta', '2026-09-01')->assertHasErrors('fechaHasta');
        $component->assertSee($one->folio)->assertSee($two->folio);
    }

    public function test_sorting_pagination_and_clear_filters_are_stable(): void
    {
        foreach (range(1, 11) as $index) {
            $this->pago(['folio' => sprintf('PAG-%02d', $index), 'fecha_pago' => '2026-09-20 10:00:00']);
        }
        $component = Livewire::actingAs($this->admin)->test(ShowCobranza::class)
            ->assertSeeInOrder(['PAG-11', 'PAG-10'])->assertDontSee('PAG-01')
            ->set('porPagina', 25)->assertSee('PAG-01')
            ->set('busqueda', 'PAG-11')->assertDontSee('PAG-10')
            ->call('limpiarFiltros')->assertSet('busqueda', '')->assertSet('porPagina', 10)->assertSee('PAG-10');
    }

    public function test_render_and_filter_actions_never_write_financial_records(): void
    {
        $payment = $this->pago(['folio' => 'INTOCABLE']);
        $cargo = $this->cargo('88.88', Cargo::ESTADO_PENDIENTE);
        $before = [$payment->fresh()->getAttributes(), $cargo->fresh()->getAttributes(), Pago::count(), Cargo::count()];
        Livewire::actingAs($this->admin)->test(ShowCobranza::class)->set('busqueda', 'INTOCABLE')->call('limpiarFiltros');
        $this->assertSame($before, [$payment->fresh()->getAttributes(), $cargo->fresh()->getAttributes(), Pago::count(), Cargo::count()]);
    }

    public function test_financial_menu_is_authorized_and_uses_existing_routes(): void
    {
        $html = $this->actingAs($this->admin)->get(route('facturacion.cobranza'));
        $html->assertSee('Facturación y pagos')->assertSee('Cobranza')->assertSee('Cargos')
            ->assertSee('Pagos')->assertSee('Registrar pago')->assertSee('Configuración')
            ->assertDontSee('Estado de cuenta')->assertDontSee('Facturación CFDI');

        $unauthorized = $this->user('venta');
        $this->actingAs($unauthorized)->get(route('dashboard'))->assertDontSee('Facturación y pagos');
    }

    private function pago(array $changes = []): Pago
    {
        $attributes = array_merge([
            'folio' => uniqid('PAGO-', true), 'inscripciones_id' => $this->inscripcion->getKey(),
            'prospectos_id' => $this->inscripcion->prospectos_id, 'responsable_pago_id' => $this->responsable->getKey(),
            'fecha_pago' => '2026-09-15 12:00:00', 'zona_horaria' => 'UTC', 'moneda' => 'MXN',
            'monto' => '10.00', 'metodo_pago_id' => MetodoPago::query()->firstOrFail()->getKey(),
            'estado' => Pago::ESTADO_CONFIRMADO, 'created_by' => $this->admin->getKey(),
        ], $changes);

        return Pago::forceCreate($attributes);
    }

    private function cargo(string $saldo, string $estado): Cargo
    {
        return Cargo::create([
            'inscripciones_id' => $this->inscripcion->getKey(), 'concepto_cobro_id' => 1,
            'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-30', 'moneda' => 'MXN',
            'subtotal' => $saldo, 'total' => $saldo, 'saldo_pendiente' => $saldo,
            'estado' => $estado, 'origen' => Cargo::ORIGEN_MANUAL,
        ]);
    }
}
