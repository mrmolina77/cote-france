<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowMetodosPago;
use App\Models\MetodoPago;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MetodoPagoSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
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

    public function test_payment_method_navigation_links_are_active_on_their_route(): void
    {
        $this->actingAs($this->user('admin'));
        $url = route('configuracion.metodos-pago');

        $aside = $this->navigationView('components.layout.aside', 'configuracion.metodos-pago');
        $this->assertStringContainsString($url, $aside); $this->assertStringContainsString('Métodos de pago', $aside); $this->assertStringContainsString('bg-blue-600 text-white', $aside);
        $this->assertMatchesRegularExpression('/href="'.preg_quote($url, '/').'"[\s\S]*?class="bg-blue-600 text-white[^\"]*"/', $aside);

        $mobile = $this->navigationView('components.layout.mobile-header', 'configuracion.metodos-pago');
        $this->assertStringContainsString($url, $mobile); $this->assertStringContainsString('Métodos de pago', $mobile);
        $this->assertMatchesRegularExpression('/href="'.preg_quote($url, '/').'" class="[^"]*active-nav-link[^"]*"/', $mobile);
    }

    public function test_payment_method_navigation_links_remain_visible_but_inactive_on_another_route(): void
    {
        $this->actingAs($this->user('admin'));
        $url = route('configuracion.metodos-pago');
        $aside = $this->navigationView('components.layout.aside', 'dashboard');
        $this->assertStringContainsString($url, $aside); $this->assertStringContainsString('Métodos de pago', $aside);
        $this->assertMatchesRegularExpression('/href="'.preg_quote($url, '/').'"[\s\S]*?class="text-gray-300 hover:bg-gray-700 hover:text-white[^"]*"/', $aside);

        $mobile = $this->navigationView('components.layout.mobile-header', 'dashboard');
        $this->assertStringContainsString($url, $mobile); $this->assertStringContainsString('Métodos de pago', $mobile);
        $this->assertMatchesRegularExpression('/href="'.preg_quote($url, '/').'" class="[^"]*opacity-75 hover:opacity-100[^"]*"/', $mobile);
        $this->assertDoesNotMatchRegularExpression('/href="'.preg_quote($url, '/').'" class="[^"]*active-nav-link[^"]*"/', $mobile);
    }

    public function test_create_and_close_restore_the_complete_pristine_form(): void
    {
        $this->actingAs($this->user('admin'));
        $component = Livewire::test(ShowMetodosPago::class)
            ->set('clave', 'MALA CLAVE')->set('nombre', '')->call('store')->assertHasErrors(['clave', 'nombre'])
            ->call('create')->assertSet('open_form', true);
        $this->assertPristineForm($component);

        $before = MetodoPago::count();
        $component->set('clave', 'TEMPORAL')->set('nombre', 'Temporal')->call('closeForm')->assertSet('open_form', false);
        $this->assertPristineForm($component);
        $this->assertSame($before, MetodoPago::count());
    }

    /** @dataProvider requirementFields */
    public function test_each_requirement_is_created_loaded_unchecked_and_rejected_atomically(string $field): void
    {
        $this->actingAs($this->user('admin'));
        $create = Livewire::test(ShowMetodosPago::class)->set('clave', 'ALTA_'.$this->fieldSuffix($field))->set('nombre', 'Alta')->set($field, true)->call('store')->assertHasNoErrors();
        $method = MetodoPago::first();
        $this->assertTrue($method->{$field});
        $this->assertPristineForm($create);
        Livewire::test(ShowMetodosPago::class)->set('clave', 'BAJA_'.$this->fieldSuffix($field))->set('nombre', 'Baja')->set($field, false)->call('store')->assertHasNoErrors();
        $this->assertFalse(MetodoPago::where('clave', 'BAJA_'.$this->fieldSuffix($field))->firstOrFail()->{$field});

        Livewire::test(ShowMetodosPago::class)->call('edit', $method->getKey())->assertSet($field, true)->set($field, false)->call('update')->assertHasNoErrors();
        $this->assertFalse($method->refresh()->{$field});

        $snapshot = $method->getAttributes();
        Livewire::test(ShowMetodosPago::class)->call('edit', $method->getKey())->set('nombre', 'No debe persistir')->set($field, 'no-booleano')->call('update')->assertHasErrors([$field => 'boolean']);
        $this->assertSame($snapshot, $method->fresh()->getAttributes());

        $count = MetodoPago::count();
        Livewire::test(ShowMetodosPago::class)->set('clave', 'INVALIDA_'.$this->fieldSuffix($field))->set('nombre', 'Inválida')->set($field, 'no-booleano')->call('store')->assertHasErrors([$field => 'boolean']);
        $this->assertSame($count, MetodoPago::count());
    }

    public static function requirementFields(): array
    {
        return array_map(static fn ($field) => [$field], array_keys(ShowMetodosPago::REQUIREMENT_LABELS));
    }

    public function test_complete_update_changes_only_selected_record_and_cleans_form(): void
    {
        $this->actingAs($this->user('admin'));
        $selected = $this->method(['clave' => 'INMUTABLE', 'nombre' => 'Anterior']);
        $other = $this->method(['clave' => 'OTRO', 'nombre' => 'Intacto']);
        $component = Livewire::test(ShowMetodosPago::class)->call('edit', $selected->getKey())->set('clave', 'ALTERADA')
            ->set('nombre', ' Nuevo ')->set('descripcion', ' ')->set('clave_forma_pago_sat', ' ')->set('orden', 65535)->set('activo', false);
        foreach (array_keys(ShowMetodosPago::REQUIREMENT_LABELS) as $index => $field) {
            $value = $index % 2 === 0 && $field !== 'requiere_rastreo_spei';
            $component->set($field, $value);
        }
        $component->call('update')->assertHasNoErrors()->assertEmitted('alert', 'El método de pago fue actualizado satisfactoriamente.')->assertSet('open_form', false);
        $selected->refresh();
        $this->assertSame('INMUTABLE', $selected->clave); $this->assertSame('Nuevo', $selected->nombre); $this->assertNull($selected->descripcion); $this->assertNull($selected->clave_forma_pago_sat); $this->assertSame(65535, $selected->orden); $this->assertFalse($selected->activo);
        foreach (array_keys(ShowMetodosPago::REQUIREMENT_LABELS) as $index => $field) $this->assertSame($index % 2 === 0 && $field !== 'requiere_rastreo_spei', (bool) $selected->{$field});
        $this->assertSame('Intacto', $other->fresh()->nombre); $this->assertDatabaseCount('metodos_pago', 2); $this->assertPristineForm($component);
    }

    /** @dataProvider nonexistentActions */
    public function test_nonexistent_identifiers_return_404_without_writes(string $action): void
    {
        $this->actingAs($this->user('admin')); $method = $this->method(); $snapshot = $method->getAttributes();
        $component = Livewire::test(ShowMetodosPago::class);
        if ($action === 'update') $component->set('editingId', 999999)->set('editingSignature', 'x')->call($action)->assertStatus(404);
        else $component->call($action, 999999)->assertStatus(404);
        $this->assertSame($snapshot, $method->fresh()->getAttributes()); $this->assertDatabaseCount('metodos_pago', 1);
    }

    public static function nonexistentActions(): array { return [['edit'], ['update'], ['activar'], ['desactivar'], ['toggleEstado']]; }

    /** @dataProvider invalidSignatures */
    public function test_invalid_or_mismatched_edit_signatures_return_404(string $kind): void
    {
        $this->actingAs($this->user('admin')); $one = $this->method(['clave' => 'UNO']); $two = $this->method(['clave' => 'DOS']); $before = $two->getAttributes();
        $component = Livewire::test(ShowMetodosPago::class)->call('edit', $one->getKey());
        if ($kind === 'other-id') $component->set('editingId', $two->getKey());
        elseif ($kind === 'empty') $component->set('editingSignature', '');
        else $component->set('editingSignature', 'firma-alterada');
        $component->set('nombre', 'Hack')->call('update')->assertStatus(404);
        $this->assertSame($before, $two->fresh()->getAttributes()); $this->assertSame('Método', $one->fresh()->nombre);
    }

    public static function invalidSignatures(): array { return [['other-id'], ['empty'], ['altered']]; }

    public function test_repeated_activation_events_are_idempotent_and_only_change_status(): void
    {
        $this->actingAs($this->user('admin')); $method = $this->method(['activo' => true, 'descripcion' => 'Conservar', 'orden' => 9, 'requiere_comprobante' => true]); $immutable = $method->only(array_diff($method->getFillable(), ['activo'])); $count = MetodoPago::count();
        Livewire::test(ShowMetodosPago::class)->emit('desactivarMetodoPagoConfirmado', $method->getKey())->assertEmitted('alert', 'El método de pago fue desactivado.')->emit('desactivarMetodoPagoConfirmado', $method->getKey());
        $this->assertFalse($method->refresh()->activo); $this->assertSame($immutable, $method->only(array_keys($immutable)));
        Livewire::test(ShowMetodosPago::class)->emit('activarMetodoPagoConfirmado', $method->getKey())->assertEmitted('alert', 'El método de pago fue activado.')->emit('activarMetodoPagoConfirmado', $method->getKey());
        $this->assertTrue($method->refresh()->activo); $this->assertSame($immutable, $method->only(array_keys($immutable))); $this->assertSame($count, MetodoPago::count());
    }

    public function test_seeded_catalog_can_be_toggled_and_rendering_is_read_only(): void
    {
        $this->actingAs($this->user('admin')); $this->seed(MetodoPagoSeeder::class);
        $catalogFields = array_merge(['clave', 'nombre', 'descripcion', 'clave_forma_pago_sat', 'activo', 'orden'], array_keys(ShowMetodosPago::REQUIREMENT_LABELS));
        $before = MetodoPago::orderBy('metodo_pago_id')->get()->map->only($catalogFields)->all(); $method = MetodoPago::first(); $count = count($before);
        Livewire::test(ShowMetodosPago::class)->assertSee($method->clave)->call('desactivar', $method->getKey());
        $changed = $method->fresh(); $this->assertSame($method->clave, $changed->clave); $this->assertFalse($changed->activo); $this->assertDatabaseCount('metodos_pago', $count);
        Livewire::test(ShowMetodosPago::class)->call('activar', $method->getKey()); $this->assertDatabaseCount('metodos_pago', $count);
        Livewire::test(ShowMetodosPago::class)->assertSee($method->clave);
        $this->assertSame($before, MetodoPago::orderBy('metodo_pago_id')->get()->map->only($catalogFields)->all());
    }

    /** @dataProvider searchTerms */
    public function test_search_fields_partial_whitespace_and_empty_results_are_read_only(string $term, bool $finds): void
    {
        $this->actingAs($this->user('admin')); $match = $this->method(['clave' => 'TRANSFERENCIA', 'nombre' => 'Pago bancario', 'descripcion' => 'Referencia especial', 'clave_forma_pago_sat' => '03']); $other = $this->method(['clave' => 'EFECTIVO', 'nombre' => 'Caja']); $before = MetodoPago::orderBy('metodo_pago_id')->get()->map->getAttributes()->all();
        $test = Livewire::test(ShowMetodosPago::class)->set('page', 2)->set('search', $term)->assertSet('page', 1);
        $finds ? $test->assertSee($match->clave)->assertDontSee($other->clave) : $test->assertSee('No se encontraron métodos de pago.');
        $this->assertSame($before, MetodoPago::orderBy('metodo_pago_id')->get()->map->getAttributes()->all());
    }

    public static function searchTerms(): array { return [['TRANSFER', true], ['bancario', true], ['especial', true], [' 03 ', true], ['inexistente', false]]; }

    public function test_empty_search_returns_all_records(): void
    {
        $this->actingAs($this->user('admin')); $this->method(['clave' => 'UNO']); $this->method(['clave' => 'DOS']); Livewire::test(ShowMetodosPago::class)->set('search', '   ')->assertSee('UNO')->assertSee('DOS');
    }

    /** @dataProvider statusFilters */
    public function test_each_status_filter_is_correct_and_read_only(string $filter, bool $seesActive, bool $seesInactive): void
    {
        $this->actingAs($this->user('admin')); $this->method(['clave' => 'PAGO_ACTIVO_SOLO', 'activo' => true]); $this->method(['clave' => 'PAGO_INACTIVO_SOLO', 'activo' => false]); $before = MetodoPago::all()->map->getAttributes()->all();
        $test = Livewire::test(ShowMetodosPago::class)->set('page', 2)->set('estado', $filter)->assertSet('page', 1);
        $seesActive ? $test->assertSee('PAGO_ACTIVO_SOLO') : $test->assertDontSee('PAGO_ACTIVO_SOLO'); $seesInactive ? $test->assertSee('PAGO_INACTIVO_SOLO') : $test->assertDontSee('PAGO_INACTIVO_SOLO');
        $this->assertSame($before, MetodoPago::all()->map->getAttributes()->all());
    }

    public static function statusFilters(): array { return [['todos', true, true], ['activos', true, false], ['inactivos', false, true], ['manipulado', true, true]]; }

    /** @dataProvider allowedPageSizes */
    public function test_allowed_page_sizes_apply_exactly_and_reset_page($size): void
    {
        $this->actingAs($this->user('admin')); $this->method(); Livewire::test(ShowMetodosPago::class)->assertSet('cant', 25)->set('page', 2)->set('cant', $size)->assertSet('page', 1)->assertViewHas('metodos', fn ($items) => $items->perPage() === (int) $size); $this->assertDatabaseCount('metodos_pago', 1);
    }
    public static function allowedPageSizes(): array { return [[10], [25], [50], [100]]; }

    /** @dataProvider invalidPageSizes */
    public function test_manipulated_page_sizes_fall_back_to_25($size): void
    {
        $this->actingAs($this->user('admin')); Livewire::test(ShowMetodosPago::class)->set('cant', $size)->assertViewHas('metodos', fn ($items) => $items->perPage() === 25);
    }
    public static function invalidPageSizes(): array { return [[0], [-1], [11], [1000], ['arbitrario'], [null]]; }

    /** @dataProvider sortableColumns */
    public function test_every_whitelisted_column_sorts_and_toggles_safely(string $column, array $values, array $ascending, array $descending): void
    {
        $this->actingAs($this->user('admin'));
        $records = [
            'A' => $this->method(['clave' => 'SORT_A', 'nombre' => 'Gamma']),
            'B' => $this->method(['clave' => 'SORT_B', 'nombre' => 'Alfa']),
            'C' => $this->method(['clave' => 'SORT_C', 'nombre' => 'Beta']),
        ];
        foreach ($records as $key => $record) {
            if ($column === 'metodo_pago_id') {
                DB::table('metodos_pago')->where('metodo_pago_id', $record->getKey())->update(['metodo_pago_id' => $values[$key]]);
                $record = MetodoPago::findOrFail($values[$key]);
                $records[$key] = $record;
            } else {
                $record->{$column} = $values[$key];
                $record->save();
            }
        }
        $snapshot = MetodoPago::orderBy('metodo_pago_id')->get()->map->getAttributes()->all();
        $test = Livewire::test(ShowMetodosPago::class)->call('order', $column)->assertSet('sort', $column);
        $first = $test->get('direction'); $this->assertContains($first, ['asc', 'desc']);
        $expectedFirst = $first === 'asc' ? $ascending : $descending;
        $test->assertViewHas('metodos', fn ($items) => $items->pluck('clave')->all() === array_map(fn ($key) => $records[$key]->clave, $expectedFirst));
        $test->call('order', $column); $second = $test->get('direction');
        $this->assertNotSame($first, $second); $this->assertContains($second, ['asc', 'desc']);
        $expectedSecond = $second === 'asc' ? $ascending : $descending;
        $test->assertViewHas('metodos', fn ($items) => $items->pluck('clave')->all() === array_map(fn ($key) => $records[$key]->clave, $expectedSecond));
        $this->assertSame($snapshot, MetodoPago::orderBy('metodo_pago_id')->get()->map->getAttributes()->all());
    }
    public static function sortableColumns(): array
    {
        return [
            'internal id' => ['metodo_pago_id', ['A' => 30, 'B' => 10, 'C' => 20], ['B', 'C', 'A'], ['A', 'C', 'B']],
            'internal key' => ['clave', ['A' => 'ZZ', 'B' => 'AA', 'C' => 'MM'], ['B', 'C', 'A'], ['A', 'C', 'B']],
            'name' => ['nombre', ['A' => 'Gamma', 'B' => 'Alfa', 'C' => 'Beta'], ['B', 'C', 'A'], ['A', 'C', 'B']],
            'SAT key with ties' => ['clave_forma_pago_sat', ['A' => '03', 'B' => '01', 'C' => '01'], ['B', 'C', 'A'], ['A', 'B', 'C']],
            'active with ties' => ['activo', ['A' => true, 'B' => false, 'C' => false], ['B', 'C', 'A'], ['A', 'B', 'C']],
            'order with ties' => ['orden', ['A' => 2, 'B' => 1, 'C' => 1], ['B', 'C', 'A'], ['A', 'B', 'C']],
        ];
    }

    public function test_default_order_uses_name_as_tie_breaker(): void
    {
        $this->actingAs($this->user('admin')); $this->method(['clave' => 'Z', 'nombre' => 'Zeta', 'orden' => 1]); $this->method(['clave' => 'A', 'nombre' => 'Alfa', 'orden' => 1]); $this->method(['clave' => 'M', 'nombre' => 'Primero', 'orden' => 0]);
        Livewire::test(ShowMetodosPago::class)->assertViewHas('metodos', fn ($items) => $items->pluck('clave')->all() === ['M', 'A', 'Z']);
    }

    /** @dataProvider unsafeSortValues */
    public function test_manipulated_sorting_falls_back_without_schema_or_data_damage($column, $direction): void
    {
        $this->actingAs($this->user('admin')); $method = $this->method(); $before = $method->getAttributes();
        Livewire::test(ShowMetodosPago::class)->set('sort', $column)->set('direction', $direction)->assertSet('sort', 'orden')->assertSet('direction', 'asc');
        $this->assertTrue(Schema::hasTable('users')); $this->assertTrue(Schema::hasTable('metodos_pago')); $this->assertSame($before, $method->fresh()->getAttributes());
    }
    public static function unsafeSortValues(): array { return [['nombre; DROP TABLE users', 'asc'], ['created_at', 'sideways'], ['password', 'DESC; DROP TABLE users'], [1, 1], [null, null]]; }

    public function test_listing_renders_business_labels_states_fallbacks_and_actions(): void
    {
        $this->actingAs($this->user('admin')); $active = $this->method(['clave' => 'CERO01', 'nombre' => 'Completo', 'clave_forma_pago_sat' => '01', 'activo' => true]); foreach (array_keys(ShowMetodosPago::REQUIREMENT_LABELS) as $field) $active->{$field} = true; $active->save(); $this->method(['clave' => 'VACIO', 'nombre' => 'Vacío', 'clave_forma_pago_sat' => null, 'activo' => false]);
        $test = Livewire::test(ShowMetodosPago::class)->assertSee('CERO01')->assertSee('Completo')->assertSee('01')->assertSee('Sin configurar')->assertSee('Sin datos adicionales')->assertSee('Activo')->assertSee('Inactivo')->assertSee('Editar')->assertSee('Desactivar')->assertSee('Activar')->assertDontSee('requiere_rastreo_spei');
        foreach (ShowMetodosPago::REQUIREMENT_LABELS as $metadata) $test->assertSee($metadata['etiqueta']);
    }

    public function test_empty_listing_message_is_rendered(): void { $this->actingAs($this->user('admin')); Livewire::test(ShowMetodosPago::class)->assertSee('No se encontraron métodos de pago.'); }

    /** @dataProvider unauthorizedRoles */
    public function test_navigation_and_direct_component_access_are_denied_to_every_non_admin_role(?string $role): void
    {
        $user = $role === null ? User::factory()->create(['roles_id' => null]) : $this->user($role); $this->actingAs($user);
        foreach (['components.layout.aside', 'components.layout.mobile-header'] as $view) $this->assertStringNotContainsString(route('configuracion.metodos-pago'), $this->navigationView($view, 'configuracion.metodos-pago'));
        Livewire::test(ShowMetodosPago::class)->assertForbidden();
    }
    public static function unauthorizedRoles(): array { return [['venta'], ['profe'], ['alum'], [null]]; }

    /** @dataProvider validBoundaries */
    public function test_valid_normalization_and_boundaries_are_persisted(string $field, $value, $expected): void
    {
        $this->actingAs($this->user('admin')); $test = Livewire::test(ShowMetodosPago::class)->set('clave', 'VALIDA')->set('nombre', 'Nombre')->set($field, $value)->call('store')->assertHasNoErrors(); $this->assertSame($expected, MetodoPago::first()->{$field});
    }
    public static function validBoundaries(): array { return [['clave', str_repeat('A', 50), str_repeat('A', 50)], ['clave', ' abc_123 ', 'ABC_123'], ['nombre', str_repeat('N', 120), str_repeat('N', 120)], ['nombre', ' Nombre limpio ', 'Nombre limpio'], ['descripcion', ' Texto ', 'Texto'], ['descripcion', '   ', null], ['clave_forma_pago_sat', '01', '01'], ['clave_forma_pago_sat', '  ', null], ['orden', 0, 0], ['orden', 65535, 65535]]; }

    /** @dataProvider additionalInvalidBoundaries */
    public function test_invalid_boundaries_are_rejected_atomically(string $field, $value, string $rule): void
    {
        $this->actingAs($this->user('admin')); Livewire::test(ShowMetodosPago::class)->set('clave', 'VALIDA')->set('nombre', 'Nombre')->set($field, $value)->call('store')->assertHasErrors([$field => $rule]); $this->assertDatabaseCount('metodos_pago', 0);
    }
    public static function additionalInvalidBoundaries(): array { return [['clave', '', 'required'], ['clave', str_repeat('A', 51), 'max'], ['clave', 'CON-GUION', 'regex'], ['clave', 'ESPECIAL!', 'regex'], ['nombre', str_repeat('N', 121), 'max'], ['clave_forma_pago_sat', 'A1', 'regex'], ['clave_forma_pago_sat', '#1', 'regex'], ['orden', 'texto', 'integer']]; }

    /** @dataProvider invalidSatKeysForUpdate */
    public function test_invalid_sat_key_update_is_atomic(string $invalidSatKey): void
    {
        $this->actingAs($this->user('admin'));
        $method = $this->method(['clave' => 'SAT_INMUTABLE', 'nombre' => 'Original', 'descripcion' => 'Sin cambios', 'clave_forma_pago_sat' => '03', 'activo' => true, 'orden' => 7, 'requiere_banco' => false, 'requiere_terminal' => true]);
        $original = $method->getAttributes(); $count = MetodoPago::count();

        Livewire::test(ShowMetodosPago::class)->call('edit', $method->getKey())->set('clave', 'CLAVE_ALTERADA')->set('nombre', 'Modificado')->set('descripcion', 'También modificado')->set('activo', false)->set('orden', 99)->set('requiere_banco', true)->set('requiere_terminal', false)->set('clave_forma_pago_sat', $invalidSatKey)->call('update')->assertHasErrors(['clave_forma_pago_sat' => 'regex']);

        $this->assertSame($original, $method->fresh()->getAttributes());
        $this->assertSame('SAT_INMUTABLE', $method->fresh()->clave);
        $this->assertSame($count, MetodoPago::count());
    }

    public static function invalidSatKeysForUpdate(): array { return [['1'], ['001'], ['A1'], ['#1']]; }

    /** @dataProvider invalidOrdersForUpdate */
    public function test_invalid_order_update_is_atomic($invalidOrder, string $rule): void
    {
        $this->actingAs($this->user('admin'));
        $method = $this->method(['clave' => 'ORDEN_INMUTABLE', 'nombre' => 'Original', 'descripcion' => 'Sin cambios', 'clave_forma_pago_sat' => '03', 'activo' => true, 'orden' => 7, 'requiere_banco' => false]);
        $other = $this->method(['clave' => 'TESTIGO', 'nombre' => 'Testigo', 'orden' => 8, 'requiere_terminal' => true]);
        $original = $method->getAttributes(); $otherOriginal = $other->getAttributes(); $count = MetodoPago::count();

        Livewire::test(ShowMetodosPago::class)->call('edit', $method->getKey())->set('nombre', 'Modificado')->set('descripcion', 'También modificado')->set('clave_forma_pago_sat', '01')->set('activo', false)->set('requiere_banco', true)->set('orden', $invalidOrder)->call('update')->assertHasErrors(['orden' => $rule]);

        $this->assertSame($original, $method->fresh()->getAttributes());
        $this->assertSame($otherOriginal, $other->fresh()->getAttributes());
        $this->assertSame($count, MetodoPago::count());
    }

    public static function invalidOrdersForUpdate(): array { return [[-1, 'min'], [65536, 'max'], [1.5, 'integer'], ['texto', 'integer']]; }

    private function assertPristineForm($component): void
    {
        $component->assertSet('editingId', null)->assertSet('editingSignature', null)->assertSet('clave', '')->assertSet('nombre', '')->assertSet('descripcion', '')->assertSet('clave_forma_pago_sat', '')->assertSet('orden', 0)->assertSet('activo', true)->assertHasNoErrors();
        foreach (array_keys(ShowMetodosPago::REQUIREMENT_LABELS) as $field) $component->assertSet($field, false);
    }

    private function fieldSuffix(string $field): string { return strtoupper(substr(hash('sha1', $field), 0, 10)); }

    private function navigationView(string $view, string $routeName): string
    {
        $request = Request::create(route($routeName), 'GET');
        $route = app('router')->getRoutes()->match($request);
        $request->setRouteResolver(static fn () => $route);
        $this->app->instance('request', $request);

        return view($view)->render();
    }

    private function user(string $role): User
    {
        $roleModel = Role::create(['roles_codigo' => $role, 'roles_nombre' => $role]); return User::factory()->create(['roles_id' => $roleModel->getKey(), 'email_verified_at' => now()]);
    }

    private function method(array $attributes = []): MetodoPago
    {
        return MetodoPago::create(array_merge(['clave' => 'METODO_'.uniqid(), 'nombre' => 'Método'], $attributes))->fresh();
    }
}
