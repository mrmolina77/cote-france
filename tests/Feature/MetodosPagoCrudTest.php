<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowMetodosPago;
use App\Models\MetodoPago;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MetodosPagoCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite'); config()->set('database.connections.sqlite.database', ':memory:'); DB::purge('sqlite');
        Schema::create('roles', function (Blueprint $table) { $table->id('roles_id'); $table->string('roles_codigo'); $table->string('roles_nombre'); $table->timestamps(); });
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('email')->unique(); $table->timestamp('email_verified_at')->nullable(); $table->string('password'); $table->text('two_factor_secret')->nullable(); $table->text('two_factor_recovery_codes')->nullable(); $table->rememberToken(); $table->foreignId('current_team_id')->nullable(); $table->string('profile_photo_path', 2048)->nullable(); $table->unsignedBigInteger('roles_id')->nullable(); $table->timestamps(); });
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id('metodo_pago_id'); $table->string('clave', 50)->unique(); $table->string('nombre', 120); $table->text('descripcion')->nullable(); $table->string('clave_forma_pago_sat', 2)->nullable();
            foreach (array_keys(ShowMetodosPago::REQUIREMENT_LABELS) as $field) $table->boolean($field)->default(false);
            $table->boolean('activo')->default(true); $table->unsignedSmallInteger('orden')->default(0); $table->timestamps();
        });
    }

    public function test_route_and_gate_allow_only_admin(): void
    {
        $admin = $this->user('admin'); $other = $this->user('venta'); $generic = User::factory()->create(['roles_id' => null]);
        $this->assertTrue(Gate::forUser($admin)->allows('manage-metodos-pago')); $this->assertFalse(Gate::forUser($other)->allows('manage-metodos-pago')); $this->assertFalse(Gate::forUser($generic)->allows('manage-metodos-pago'));
        $this->actingAs($admin)->get('/configuracion/metodos-pago')->assertOk()->assertSee('Métodos de pago');
        $this->actingAs($other)->get('/configuracion/metodos-pago')->assertForbidden(); $this->actingAs($generic)->get('/configuracion/metodos-pago')->assertForbidden();
        $this->app['auth']->forgetGuards(); $this->get('/configuracion/metodos-pago')->assertRedirect('/login');
    }

    public function test_unauthorized_user_cannot_mount_or_mutate(): void
    {
        $this->actingAs($this->user('venta')); Livewire::test(ShowMetodosPago::class)->assertForbidden();
        foreach ([['create', []], ['edit', [1]], ['store', []], ['update', []], ['activar', [1]], ['desactivar', [1]], ['toggleEstado', [1]]] as [$method, $args]) {
            try { (new ShowMetodosPago())->{$method}(...$args); $this->fail($method.' no autorizó la acción.'); } catch (AuthorizationException $exception) { $this->assertInstanceOf(AuthorizationException::class, $exception); }
        }
    }

    public function test_admin_creates_normalized_method_with_null_optionals_and_booleans(): void
    {
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowMetodosPago::class)->set('clave', '  transferencia_local  ')->set('nombre', '  Transferencia local  ')->set('descripcion', '')->set('clave_forma_pago_sat', '03')->set('orden', 8)->set('requiere_banco', true)->set('requiere_rastreo_spei', true)->call('store')->assertHasNoErrors()->assertEmitted('alert', 'El método de pago fue creado satisfactoriamente.');
        $method = MetodoPago::first(); $this->assertSame('TRANSFERENCIA_LOCAL', $method->clave); $this->assertSame('Transferencia local', $method->nombre); $this->assertNull($method->descripcion); $this->assertSame('03', $method->clave_forma_pago_sat); $this->assertTrue($method->requiere_banco); $this->assertTrue($method->requiere_rastreo_spei); $this->assertFalse($method->requiere_terminal);
    }

    public function test_blank_sat_is_null_and_leading_zero_is_preserved(): void
    {
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowMetodosPago::class)->set('clave', 'UNO')->set('nombre', 'Uno')->set('clave_forma_pago_sat', '')->call('store')->assertHasNoErrors();
        Livewire::test(ShowMetodosPago::class)->set('clave', 'DOS')->set('nombre', 'Dos')->set('clave_forma_pago_sat', '01')->call('store')->assertHasNoErrors();
        $this->assertNull(MetodoPago::where('clave', 'UNO')->value('clave_forma_pago_sat')); $this->assertSame('01', MetodoPago::where('clave', 'DOS')->value('clave_forma_pago_sat'));
    }

    /** @dataProvider invalidValues */
    public function test_invalid_values_do_not_create_records(string $field, $value, string $rule): void
    {
        $this->actingAs($this->user('admin')); Livewire::test(ShowMetodosPago::class)->set('clave', 'VALIDA')->set('nombre', 'Válida')->set($field, $value)->call('store')->assertHasErrors([$field => $rule]); $this->assertDatabaseCount('metodos_pago', 0);
    }

    public static function invalidValues(): array
    {
        return [['clave', 'MALA CLAVE', 'regex'], ['nombre', '', 'required'], ['orden', -1, 'min'], ['orden', 1.5, 'integer'], ['orden', 65536, 'max'], ['clave_forma_pago_sat', '1', 'regex'], ['clave_forma_pago_sat', '001', 'regex'], ['clave_forma_pago_sat', 'AA', 'regex'], ['requiere_banco', 'quizá', 'boolean']];
    }

    public function test_duplicate_key_is_rejected_without_partial_insert(): void
    {
        $this->actingAs($this->user('admin')); $this->method(['clave' => 'DUPLICADA']);
        Livewire::test(ShowMetodosPago::class)->set('clave', ' duplicada ')->set('nombre', 'Otra')->call('store')->assertHasErrors(['clave' => 'unique']); $this->assertDatabaseCount('metodos_pago', 1);
    }

    public function test_edit_updates_configuration_but_key_is_immutable(): void
    {
        $this->actingAs($this->user('admin')); $method = $this->method(['clave' => 'FIJA', 'nombre' => 'Antes', 'requiere_banco' => true]);
        Livewire::test(ShowMetodosPago::class)->call('edit', $method->getKey())->set('clave', 'MANIPULADA')->set('nombre', ' Después ')->set('descripcion', '')->set('clave_forma_pago_sat', '02')->set('orden', 12)->set('activo', false)->set('requiere_banco', false)->set('requiere_terminal', true)->call('update')->assertHasNoErrors();
        $method->refresh(); $this->assertSame('FIJA', $method->clave); $this->assertSame('Después', $method->nombre); $this->assertNull($method->descripcion); $this->assertSame('02', $method->clave_forma_pago_sat); $this->assertFalse($method->requiere_banco); $this->assertTrue($method->requiere_terminal); $this->assertDatabaseCount('metodos_pago', 1);
    }

    public function test_invalid_update_is_atomic_and_tampered_editing_id_cannot_update_another_record(): void
    {
        $this->actingAs($this->user('admin')); $one = $this->method(['clave' => 'UNO', 'nombre' => 'Uno']); $two = $this->method(['clave' => 'DOS', 'nombre' => 'Dos']);
        Livewire::test(ShowMetodosPago::class)->call('edit', $one->getKey())->set('nombre', '')->call('update')->assertHasErrors('nombre'); $this->assertSame('Uno', $one->refresh()->nombre);
        Livewire::test(ShowMetodosPago::class)->call('edit', $one->getKey())->set('editingId', $two->getKey())->set('nombre', 'Hack')->call('update')->assertStatus(404); $this->assertSame('Dos', $two->refresh()->nombre);
        Livewire::test(ShowMetodosPago::class)->set('editingId', 999999)->call('update')->assertStatus(404);
    }

    public function test_activation_changes_only_status_and_there_is_no_delete_action(): void
    {
        $this->actingAs($this->user('admin')); $method = $this->method(['activo' => true, 'requiere_banco' => true])->fresh(); $before = $method->only(array_diff($method->getFillable(), ['activo']));
        Livewire::test(ShowMetodosPago::class)->call('desactivar', $method->getKey()); $this->assertFalse($method->refresh()->activo); $this->assertSame($before, $method->only(array_keys($before)));
        Livewire::test(ShowMetodosPago::class)->call('activar', $method->getKey()); $this->assertTrue($method->refresh()->activo); $this->assertFalse(method_exists(ShowMetodosPago::class, 'delete'));
    }

    public function test_search_status_pagination_and_sorting_are_safe(): void
    {
        $this->actingAs($this->user('admin')); $this->method(['clave' => 'BANCO', 'nombre' => 'Zeta', 'descripcion' => 'especial', 'clave_forma_pago_sat' => '03', 'activo' => true]); $this->method(['clave' => 'CAJA', 'nombre' => 'Alfa', 'activo' => false]);
        foreach (['BANCO', 'Zeta', 'especial', '03'] as $term) Livewire::test(ShowMetodosPago::class)->set('search', ' '.$term.' ')->assertSee('BANCO')->assertDontSee('CAJA');
        Livewire::test(ShowMetodosPago::class)->set('estado', 'inactivos')->assertSee('CAJA')->assertDontSee('BANCO');
        Livewire::test(ShowMetodosPago::class)->set('cant', 1000)->assertViewHas('metodos', fn ($items) => $items->perPage() === 25)->set('sort', 'nombre; DROP TABLE users')->set('direction', 'sideways')->assertSee('BANCO')->assertSet('sort', 'orden')->assertSet('direction', 'asc'); $this->assertTrue(Schema::hasTable('users'));
    }

    public function test_filters_and_page_size_reset_pagination(): void
    {
        $this->actingAs($this->user('admin')); $component = Livewire::test(ShowMetodosPago::class)->set('page', 3)->set('search', 'x')->assertSet('page', 1)->set('page', 3)->set('estado', 'activos')->assertSet('page', 1)->set('page', 3)->set('cant', 10)->assertSet('page', 1); $this->assertNotNull($component);
    }

    public function test_navigation_is_visible_only_to_admin(): void
    {
        $admin = $this->actingAs($this->user('admin')); foreach (['components.layout.aside', 'components.layout.mobile-header'] as $view) $admin->view($view)->assertSee('Métodos de pago')->assertSee(route('configuracion.metodos-pago'));
        $other = $this->actingAs($this->user('venta')); foreach (['components.layout.aside', 'components.layout.mobile-header'] as $view) $other->view($view)->assertDontSee('Métodos de pago');
    }

    private function user(string $role): User
    {
        $roleModel = Role::create(['roles_codigo' => $role, 'roles_nombre' => $role]); return User::factory()->create(['roles_id' => $roleModel->getKey(), 'email_verified_at' => now()]);
    }

    private function method(array $attributes = []): MetodoPago
    {
        return MetodoPago::create(array_merge(['clave' => 'METODO_'.uniqid(), 'nombre' => 'Método'], $attributes));
    }
}
