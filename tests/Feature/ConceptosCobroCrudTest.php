<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowConceptosCobro;
use App\Models\ConceptoCobro;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ConceptoCobroSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Tests\TestCase;

class ConceptosCobroCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Schema::create('roles', function (Blueprint $table) {
            $table->id('roles_id'); $table->string('roles_codigo'); $table->string('roles_nombre'); $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->unique(); $table->timestamp('email_verified_at')->nullable(); $table->string('password');
            $table->text('two_factor_secret')->nullable(); $table->text('two_factor_recovery_codes')->nullable(); $table->rememberToken();
            $table->foreignId('current_team_id')->nullable(); $table->string('profile_photo_path', 2048)->nullable(); $table->unsignedBigInteger('roles_id')->nullable(); $table->timestamps();
        });
        Schema::create('conceptos_cobro', function (Blueprint $table) {
            $table->id('concepto_cobro_id'); $table->string('clave', 50)->unique(); $table->string('nombre', 120); $table->text('descripcion')->nullable();
            $table->string('clave_producto_servicio_sat', 20)->nullable(); $table->string('clave_unidad_sat', 10)->nullable(); $table->string('objeto_impuesto_sat', 10)->nullable();
            $table->decimal('tasa_iva', 8, 6)->nullable(); $table->boolean('activo')->default(true); $table->unsignedSmallInteger('orden')->default(0); $table->timestamps();
        });
    }

    /** @dataProvider deniedRoles */
    public function test_only_admin_can_access_route(string $role): void
    {
        $this->actingAs($this->user($role))->get('/configuracion/conceptos-cobro')->assertForbidden();
    }

    public static function deniedRoles(): array { return [['venta'], ['profe'], ['alum'], ['otro']]; }

    public function test_admin_can_access_and_guest_is_redirected(): void
    {
        $this->actingAs($this->user('admin'))->get('/configuracion/conceptos-cobro')->assertOk()->assertSee('Conceptos de cobro');
        auth()->logout();
        $this->get('/configuracion/conceptos-cobro')->assertRedirect('/login');
    }

    public function test_gate_allows_only_admin_including_user_without_role(): void
    {
        $admin = $this->user('admin'); $sale = $this->user('venta');
        $generic = User::factory()->create(['roles_id' => null]);
        $this->assertTrue(Gate::forUser($admin)->allows('manage-conceptos-cobro'));
        $this->assertFalse(Gate::forUser($sale)->allows('manage-conceptos-cobro'));
        $this->assertFalse(Gate::forUser($generic)->allows('manage-conceptos-cobro'));
        $this->actingAs($generic)->get('/configuracion/conceptos-cobro')->assertForbidden();
    }

    public function test_unauthorized_user_cannot_mount_component(): void
    {
        $this->actingAs($this->user('venta'));
        $this->expectException(AuthorizationException::class);
        Livewire::test(ShowConceptosCobro::class);
    }

    /** @dataProvider protectedMutations */
    public function test_unauthorized_user_cannot_call_mutations_directly(string $method, array $arguments): void
    {
        $this->actingAs($this->user('venta'));
        $this->expectException(AuthorizationException::class);
        (new ShowConceptosCobro())->{$method}(...$arguments);
    }

    public static function protectedMutations(): array
    {
        return [
            ['create', []], ['edit', [1]], ['store', []], ['update', []],
            ['activar', [1]], ['desactivar', [1]], ['toggleEstado', [1]],
        ];
    }

    public function test_admin_creates_normalized_concept_and_blank_fiscal_fields_become_null(): void
    {
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowConceptosCobro::class)->set('clave', '  curso_especial_2  ')->set('nombre', '  Curso dos  ')
            ->set('descripcion', '')->set('clave_producto_servicio_sat', '')->set('clave_unidad_sat', '')
            ->set('objeto_impuesto_sat', '')->set('tasa_iva', '')->set('orden', 12)->call('store')->assertHasNoErrors();
        $concepto = ConceptoCobro::first();
        $this->assertSame('CURSO_ESPECIAL_2', $concepto->clave); $this->assertSame('Curso dos', $concepto->nombre);
        foreach (['descripcion', 'clave_producto_servicio_sat', 'clave_unidad_sat', 'objeto_impuesto_sat', 'tasa_iva'] as $field) {
            $this->assertNull($concepto->{$field}, "{$field} should be stored as null");
        }
    }

    public function test_creation_validation_rejects_duplicate_invalid_and_required_values(): void
    {
        $this->actingAs($this->user('admin')); ConceptoCobro::create(['clave' => 'DUPLICADA', 'nombre' => 'Uno']);
        Livewire::test(ShowConceptosCobro::class)->set('clave', 'duplicada')->set('nombre', 'Dos')->call('store')->assertHasErrors(['clave' => 'unique']);
        Livewire::test(ShowConceptosCobro::class)->set('clave', 'MALA CLAVE!')->set('nombre', '')->call('store')->assertHasErrors(['clave' => 'regex', 'nombre' => 'required']);
        Livewire::test(ShowConceptosCobro::class)->set('clave', '')->set('nombre', '')->call('store')->assertHasErrors(['clave' => 'required', 'nombre' => 'required']);
    }

    /** @dataProvider invalidNumericValues */
    public function test_numeric_limits_are_enforced(string $field, $value): void
    {
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowConceptosCobro::class)->set('clave', 'VALIDA')->set('nombre', 'Válida')->set($field, $value)->call('store')->assertHasErrors([$field]);
    }

    public static function invalidNumericValues(): array
    {
        return [['orden', -1], ['orden', 1.5], ['orden', 65536], ['tasa_iva', -0.1], ['tasa_iva', 1.1], ['tasa_iva', 'texto']];
    }

    /** @dataProvider valuesOverMaximumLength */
    public function test_string_maximum_lengths_are_enforced(string $field, string $value): void
    {
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowConceptosCobro::class)
            ->set('clave', 'CLAVE_VALIDA')->set('nombre', 'Nombre válido')
            ->set('clave_producto_servicio_sat', '84111506')->set('clave_unidad_sat', 'E48')
            ->set('objeto_impuesto_sat', '02')->set($field, $value)->call('store')
            ->assertHasErrors([$field => 'max']);
        $this->assertDatabaseCount('conceptos_cobro', 0);
    }

    public static function valuesOverMaximumLength(): array
    {
        return [
            ['clave', str_repeat('A', 51)],
            ['nombre', str_repeat('n', 121)],
            ['clave_producto_servicio_sat', str_repeat('1', 21)],
            ['clave_unidad_sat', str_repeat('U', 11)],
            ['objeto_impuesto_sat', str_repeat('0', 11)],
        ];
    }

    /** @dataProvider valuesAtMaximumLength */
    public function test_string_maximum_length_boundaries_are_accepted(string $field, string $value): void
    {
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowConceptosCobro::class)
            ->set('clave', 'CLAVE_VALIDA')->set('nombre', 'Nombre válido')
            ->set($field, $value)->call('store')->assertHasNoErrors();
        $this->assertDatabaseCount('conceptos_cobro', 1);
        $this->assertSame($value, ConceptoCobro::first()->{$field});
    }

    public static function valuesAtMaximumLength(): array
    {
        return [
            ['clave', str_repeat('A', 50)],
            ['nombre', str_repeat('n', 120)],
            ['clave_producto_servicio_sat', str_repeat('1', 20)],
            ['clave_unidad_sat', str_repeat('U', 10)],
            ['objeto_impuesto_sat', str_repeat('0', 10)],
        ];
    }

    /** @dataProvider validTaxRates */
    public function test_valid_tax_rates_are_accepted($rate): void
    {
        $this->actingAs($this->user('admin')); $key = 'IVA'.str_replace(['.', ''], ['', 'N'], (string) $rate).uniqid();
        Livewire::test(ShowConceptosCobro::class)->set('clave', strtoupper($key))->set('nombre', 'IVA')->set('tasa_iva', $rate)->call('store')->assertHasNoErrors('tasa_iva');
    }

    public static function validTaxRates(): array { return [[''], [0], ['0.080000'], ['0.160000'], [1]]; }

    public function test_edit_updates_allowed_fields_but_never_key(): void
    {
        $this->actingAs($this->user('admin')); $concepto = ConceptoCobro::create(['clave' => 'FIJA', 'nombre' => 'Antes', 'orden' => 1]);
        $concepto->timestamps = false;
        $concepto->created_at = '2025-01-01 00:00:00';
        $concepto->updated_at = '2025-01-01 00:00:00';
        $concepto->save();
        $concepto->timestamps = true;
        Livewire::test(ShowConceptosCobro::class)->call('edit', $concepto->getKey())->set('clave', 'MANIPULADA')->set('nombre', ' Después ')
            ->set('descripcion', 'Texto')->set('clave_producto_servicio_sat', '86121500')->set('clave_unidad_sat', 'E48')
            ->set('objeto_impuesto_sat', '02')->set('tasa_iva', '0.160000')->set('orden', 20)->set('activo', false)->call('update')->assertHasNoErrors();
        $concepto->refresh();
        $this->assertSame('FIJA', $concepto->clave);
        $this->assertSame('Después', $concepto->nombre);
        $this->assertSame('Texto', $concepto->descripcion);
        $this->assertSame('86121500', $concepto->clave_producto_servicio_sat);
        $this->assertSame('E48', $concepto->clave_unidad_sat);
        $this->assertSame('02', $concepto->objeto_impuesto_sat);
        $this->assertSame('0.160000', $concepto->tasa_iva);
        $this->assertSame(20, $concepto->orden);
        $this->assertFalse($concepto->activo);
        $this->assertSame('2025-01-01 00:00:00', $concepto->created_at->format('Y-m-d H:i:s'));
        $this->assertNotSame('2025-01-01 00:00:00', $concepto->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_activation_only_changes_status_and_no_delete_operation_exists(): void
    {
        $this->actingAs($this->user('admin')); $concepto = ConceptoCobro::create([
            'clave' => 'ESTADO', 'nombre' => 'Conservar', 'descripcion' => 'dato',
            'clave_producto_servicio_sat' => '84111506', 'clave_unidad_sat' => 'E48',
            'objeto_impuesto_sat' => '02', 'tasa_iva' => '0.160000', 'activo' => true, 'orden' => 9,
        ]);
        $unchangedFields = ['clave', 'nombre', 'descripcion', 'clave_producto_servicio_sat', 'clave_unidad_sat', 'objeto_impuesto_sat', 'tasa_iva', 'orden'];
        $before = $concepto->only($unchangedFields);
        Livewire::test(ShowConceptosCobro::class)->call('desactivar', $concepto->getKey());
        $this->assertFalse($concepto->refresh()->activo); $this->assertSame($before, $concepto->only(array_keys($before)));
        Livewire::test(ShowConceptosCobro::class)->call('activar', $concepto->getKey());
        $this->assertTrue($concepto->refresh()->activo); $this->assertSame($before, $concepto->only($unchangedFields));
        $this->assertFalse(method_exists(ShowConceptosCobro::class, 'delete')); $this->assertDatabaseHas('conceptos_cobro', ['clave' => 'ESTADO']);
    }

    public function test_confirmed_events_invoke_only_the_explicit_status_actions(): void
    {
        $this->actingAs($this->user('admin'));
        $concepto = ConceptoCobro::create(['clave' => 'EVENTO', 'nombre' => 'Evento', 'activo' => true]);
        Livewire::test(ShowConceptosCobro::class)->emit('desactivarConceptoConfirmado', $concepto->getKey());
        $this->assertFalse($concepto->refresh()->activo);
        Livewire::test(ShowConceptosCobro::class)->emit('activarConceptoConfirmado', $concepto->getKey());
        $this->assertTrue($concepto->refresh()->activo);
    }

    public function test_search_filters_pagination_and_safe_sorting(): void
    {
        $this->actingAs($this->user('admin'));
        ConceptoCobro::create(['clave' => 'CLAVE_A', 'nombre' => 'Zeta', 'descripcion' => 'especial', 'activo' => true, 'orden' => 2]);
        ConceptoCobro::create(['clave' => 'CLAVE_B', 'nombre' => 'Alfa', 'activo' => false, 'orden' => 1]);
        Livewire::test(ShowConceptosCobro::class)->set('search', 'especial')->assertSee('CLAVE_A')->assertDontSee('CLAVE_B');
        Livewire::test(ShowConceptosCobro::class)->set('estado', 'inactivos')->assertSee('CLAVE_B')->assertDontSee('CLAVE_A');
        Livewire::test(ShowConceptosCobro::class)->set('cant', 10)->assertViewHas('conceptos', fn ($items) => $items->perPage() === 10)
            ->call('order', 'nombre')->assertSet('sort', 'nombre')->set('sort', 'nombre; DROP TABLE users')->set('direction', 'sideways')
            ->assertSee('CLAVE_A')->assertSet('sort', 'orden')->assertSet('direction', 'asc');
        $this->assertTrue(Schema::hasTable('users'));
    }

    /** @dataProvider menuViews */
    public function test_admin_sees_concept_link_in_each_menu(string $view): void
    {
        $this->actingAs($this->user('admin'));
        $menu = View::make($view)->render();
        $this->assertStringContainsString('Configuración', $menu);
        $this->assertStringContainsString('Conceptos de cobro', $menu);
        $this->assertStringContainsString(route('configuracion.conceptos-cobro'), $menu);
    }

    /** @dataProvider deniedMenuCases */
    public function test_unauthorized_roles_do_not_see_concept_link_in_each_menu(string $role, string $view): void
    {
        $this->actingAs($this->user($role));
        $menu = View::make($view)->render();
        $this->assertStringNotContainsString('Conceptos de cobro', $menu);
        $this->assertStringNotContainsString(route('configuracion.conceptos-cobro'), $menu);
    }

    public static function menuViews(): array
    {
        return [['components.layout.aside'], ['components.layout.mobile-header']];
    }

    public static function deniedMenuCases(): array
    {
        $cases = [];
        foreach (['venta', 'profe', 'alum'] as $role) {
            foreach (self::menuViews() as [$view]) $cases[] = [$role, $view];
        }
        return $cases;
    }

    public function test_seeded_keys_remain_unchanged_after_editing(): void
    {
        $this->seed(ConceptoCobroSeeder::class); $this->assertCount(9, ConceptoCobro::all());
        $this->actingAs($this->user('admin'));
        $keys = ConceptoCobro::pluck('clave')->all(); $first = ConceptoCobro::first();
        Livewire::test(ShowConceptosCobro::class)->call('edit', $first->getKey())->set('nombre', 'Nombre editado')->call('update');
        $this->assertSame($keys, ConceptoCobro::pluck('clave')->all());
    }

    private function user(string $code): User
    {
        $role = Role::create(['roles_codigo' => $code, 'roles_nombre' => ucfirst($code)]);
        return User::factory()->create(['roles_id' => $role->getKey(), 'email_verified_at' => now()]);
    }
}
