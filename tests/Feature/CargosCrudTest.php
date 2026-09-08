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

    /** @dataProvider unauthorizedRoles */
    public function test_each_non_admin_role_cannot_mount_livewire_or_invoke_store(string $role): void
    {
        $this->actingAs($this->user($role));
        $before = Cargo::query()->count();
        try {
            Livewire::test(ShowCargos::class)
                ->set('inscripciones_id', $this->inscripcion->getKey())
                ->set('concepto_cobro_id', $this->concepto->getKey())
                ->set('fecha_emision', '2026-01-01')->set('fecha_vencimiento', '2026-01-02')
                ->set('subtotal', '10.00')->call('store');
            $this->fail('Livewire mount/store should have been denied.');
        } catch (AuthorizationException $exception) {
            $this->assertInstanceOf(AuthorizationException::class, $exception);
        }
        $this->assertSame($before, Cargo::query()->count());
    }

    public static function unauthorizedRoles(): array
    {
        return [['venta'], ['profe'], ['alum']];
    }

    /** @dataProvider sensitiveMethods */
    public function test_every_public_sensitive_method_reauthorizes(string $method, array $arguments): void
    {
        $this->actingAs($this->user('venta'));
        $this->expectException(AuthorizationException::class);
        app(ShowCargos::class)->{$method}(...$arguments);
    }

    public static function sensitiveMethods(): array
    {
        return [['create', []], ['closeForm', []], ['order', ['cargo_id']], ['updating', ['search']]];
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

    /** @dataProvider reservedConceptKeys */
    public function test_livewire_rejects_manually_submitted_reserved_and_inactive_concepts(string $key, bool $active): void
    {
        $concepto = ConceptoCobro::where('clave', $key)->first() ?: ConceptoCobro::create(['clave' => $key, 'nombre' => $key, 'activo' => $active]);
        $concepto->update(['activo' => $active]);
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowCargos::class)->call('create')->set('inscripciones_id', $this->inscripcion->getKey())
            ->set('concepto_cobro_id', $concepto->getKey())->set('fecha_emision', '2026-01-01')
            ->set('fecha_vencimiento', '2026-01-02')->set('subtotal', '10.00')->call('store')
            ->assertHasErrors(['concepto_cobro_id'])->assertSet('open_form', true);
        $this->assertDatabaseCount('cargos', 0);
    }

    public static function reservedConceptKeys(): array
    {
        return [['INSCRIPCION', true], ['MENSUALIDAD', true], ['RECARGO', true], ['DESCUENTO', true], ['INACTIVO', false]];
    }

    public function test_full_name_search_and_strict_date_filters_are_safe_and_effective(): void
    {
        $this->inscripcion->prospecto->update(['prospectos_nombres' => 'Juan Carlos', 'prospectos_apellidos' => 'Pérez López']);
        $inside = Cargo::create($this->cargoAttributes(['fecha_vencimiento' => '2026-02-28', 'observaciones' => 'incluido']));
        $outside = Cargo::create($this->cargoAttributes(['fecha_vencimiento' => '2026-03-01', 'observaciones' => 'excluido']));
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowCargos::class)->set('search', 'Juan Pérez')->assertSee('incluido')->assertSee('excluido')
            ->set('search', '')->set('vencimiento_hasta', '2026-02-28')->assertSee('incluido')->assertDontSee('excluido')
            ->set('vencimiento_hasta', '2026-02-30')->assertSee('incluido')->assertSee('excluido')
            ->set('vencimiento_hasta', '2026-99-99')->assertSee('incluido')->assertSee('excluido');
        $this->assertTrue(Schema::hasTable('cargos'));
        $this->assertSame(2, Cargo::whereKey([$inside->getKey(), $outside->getKey()])->count());
    }

    /** @dataProvider allowedSortColumns */
    public function test_every_allowed_sort_column_is_whitelisted_and_toggles(string $column): void
    {
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowCargos::class)->call('order', $column)->assertSet('sort', $column)->assertSet('direction', 'asc')
            ->call('order', $column)->assertSet('direction', 'desc');
        $this->assertTrue(Schema::hasTable('cargos'));
    }

    public static function allowedSortColumns(): array
    {
        return array_map(fn ($column) => [$column], ['cargo_id', 'fecha_emision', 'fecha_vencimiento', 'periodo_anio',
            'periodo_mes', 'total', 'saldo_pendiente', 'estado', 'origen']);
    }

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

    private function cargoAttributes(array $changes = []): array
    {
        return array_merge(['inscripciones_id' => $this->inscripcion->getKey(), 'concepto_cobro_id' => $this->concepto->getKey(),
            'fecha_emision' => '2026-01-01', 'fecha_vencimiento' => '2026-02-01', 'moneda' => 'MXN', 'subtotal' => '10.00',
            'descuento' => '0.00', 'recargo' => '0.00', 'impuestos' => '0.00', 'total' => '10.00', 'saldo_pendiente' => '10.00',
            'estado' => 'pendiente', 'origen' => 'manual'], $changes);
    }
}
