<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowCargos;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class CargosCrudTest extends InscripcionesTestCase
{
    private $inscripcion;
    private $concepto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inscripcion = $this->enroll(...$this->catalogs());
        $this->concepto = ConceptoCobro::create(['clave' => 'MATERIAL', 'nombre' => 'Material', 'activo' => true]);
    }

    public function test_admin_accesses_route_while_guest_and_other_roles_are_denied(): void
    {
        $this->actingAs($this->user('admin'))->get('/facturacion/cargos')->assertOk()->assertSee('Cargos');
        foreach (['venta', 'profe', 'alum'] as $role) $this->actingAs($this->user($role))->get('/facturacion/cargos')->assertForbidden();
        $this->app['auth']->forgetGuards(); $this->get('/facturacion/cargos')->assertRedirect('/login');
    }

    public function test_gate_denies_user_without_role_and_direct_calls(): void
    {
        $user = User::factory()->create(['roles_id' => 999999]);
        $this->assertFalse(Gate::forUser($user)->allows('manage-cargos'));
        $this->actingAs($user)->get('/facturacion/cargos')->assertForbidden();
        $this->actingAs($this->user('venta')); $this->expectException(AuthorizationException::class); (new ShowCargos())->create();
    }

    public function test_form_opens_with_safe_defaults_and_only_active_allowed_concepts(): void
    {
        $this->actingAs($this->user('admin'));
        ConceptoCobro::create(['clave' => 'INACTIVO', 'nombre' => 'Inactivo', 'activo' => false]);
        Livewire::test(ShowCargos::class)->call('create')->assertSet('open_form', true)->assertSet('subtotal', '')
            ->assertSee('MATERIAL')->assertDontSee('INACTIVO')->assertDontSee('MENSUALIDAD')->call('closeForm')->assertSet('open_form', false)->assertSet('fecha_emision', '');
    }

    public function test_admin_creates_charge_with_protected_server_values_and_alert(): void
    {
        $admin = $this->user('admin'); $this->actingAs($admin);
        Livewire::test(ShowCargos::class)->call('create')->set('inscripciones_id', $this->inscripcion->getKey())->set('concepto_cobro_id', $this->concepto->getKey())
            ->set('fecha_emision', '2025-01-01')->set('fecha_vencimiento', '2025-01-01')->set('subtotal', '25.5')->set('observaciones', 'Manual')
            ->call('store')->assertHasNoErrors()->assertSet('open_form', false)->assertEmitted('alert', 'El cargo extraordinario fue creado satisfactoriamente.');
        $cargo = Cargo::first()->fresh();
        $this->assertSame(['25.50', '25.50', '25.50', '0.00', '0.00', '0.00'], [$cargo->subtotal, $cargo->total, $cargo->saldo_pendiente, $cargo->descuento, $cargo->recargo, $cargo->impuestos]);
        $this->assertSame([Cargo::ORIGEN_MANUAL, Cargo::ESTADO_PENDIENTE, 'MXN', null, $admin->getKey()], [$cargo->origen, $cargo->estado, $cargo->moneda, $cargo->clave_idempotencia, $cargo->created_by]);
    }

    /** @dataProvider invalidFormValues */
    public function test_validates_manual_charge_fields(string $field, $value): void
    {
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowCargos::class)->call('create')->set('inscripciones_id', $this->inscripcion->getKey())->set('concepto_cobro_id', $this->concepto->getKey())
            ->set('fecha_emision', '2026-01-01')->set('fecha_vencimiento', '2026-01-02')->set('subtotal', '10.00')->set($field, $value)->call('store')->assertHasErrors([$field]);
        $this->assertDatabaseCount('cargos', 0);
    }

    public static function invalidFormValues(): array { return [['inscripciones_id', ''], ['inscripciones_id', 99999], ['concepto_cobro_id', ''], ['concepto_cobro_id', 99999], ['fecha_emision', ''], ['fecha_vencimiento', ''], ['fecha_vencimiento', '2025-01-01'], ['subtotal', ''], ['subtotal', '0'], ['subtotal', '-1'], ['subtotal', '1.001'], ['observaciones', 'x'.str_repeat('x', 1000)]]; }

    public function test_list_searches_filters_formats_period_and_uses_safe_sorting_and_pagination(): void
    {
        $admin = $this->user('admin'); $this->actingAs($admin);
        foreach ([['manual','pendiente',null,null,'Nota única'], ['automatico','pagado',2027,2,'Automático']] as [$origen,$estado,$anio,$mes,$nota]) {
            Cargo::create(['inscripciones_id'=>$this->inscripcion->getKey(),'concepto_cobro_id'=>$this->concepto->getKey(),'fecha_emision'=>'2026-01-01','fecha_vencimiento'=>'2026-02-01','moneda'=>'MXN','subtotal'=>'10.00','descuento'=>'0.00','recargo'=>'0.00','impuestos'=>'0.00','total'=>'10.00','saldo_pendiente'=>'10.00','estado'=>$estado,'origen'=>$origen,'periodo_anio'=>$anio,'periodo_mes'=>$mes,'observaciones'=>$nota]);
        }
        Livewire::test(ShowCargos::class)->assertSee('Alumno')->assertSee('Sin periodo')->assertSee('2027-02')->set('search', 'Nota única')->assertSee('Nota única')->assertDontSee('Automático')
            ->set('search', '')->set('origen', 'automatico')->assertSee('Automático')->assertDontSee('Nota única')->set('cant', 10)->assertViewHas('cargos', fn($items) => $items->perPage() === 10)
            ->set('sort', 'cargo_id; DROP TABLE users')->set('direction', 'sideways')->assertSet('sort', 'fecha_vencimiento')->assertSet('direction', 'desc');
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertFalse(method_exists(ShowCargos::class, 'update')); $this->assertFalse(method_exists(ShowCargos::class, 'delete'));
    }
}
