<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowCargos;
use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\User;
use App\Services\Facturacion\CreadorCargoManualService;
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
    public function test_each_non_admin_role_cannot_mount_livewire(string $role): void
    {
        $this->actingAs($this->user($role));
        $before = Cargo::query()->count();
        try {
            Livewire::test(ShowCargos::class)
                ->set('inscripciones_id', $this->inscripcion->getKey())
                ->set('concepto_cobro_id', $this->concepto->getKey())
                ->set('fecha_emision', '2026-01-01')->set('fecha_vencimiento', '2026-01-02')
                ->set('subtotal', '10.00')->call('store');
            $this->fail('Livewire mount should have been denied.');
        } catch (AuthorizationException $exception) {
            $this->assertInstanceOf(AuthorizationException::class, $exception);
        }
        $this->assertSame($before, Cargo::query()->count());
    }

    public static function unauthorizedRoles(): array
    {
        return [['venta'], ['profe'], ['alum']];
    }

    /** @dataProvider unauthorizedSensitiveCalls */
    public function test_every_public_sensitive_method_reauthorizes_before_side_effects(string $role, string $method): void
    {
        $user = $role === 'invalid' ? User::factory()->create(['roles_id' => 999999]) : $this->user($role);
        $existing = Cargo::create($this->cargoAttributes(['observaciones' => 'sin cambios']));
        $before = $existing->fresh()->getRawOriginal();
        $this->actingAs($user);
        $arguments = $method === 'store' ? [app(CreadorCargoManualService::class)] : ($method === 'order' ? ['cargo_id'] : ($method === 'updating' ? ['search'] : []));

        try {
            app(ShowCargos::class)->{$method}(...$arguments);
            $this->fail("{$method} should independently deny {$role}.");
        } catch (AuthorizationException $exception) {
            $this->assertInstanceOf(AuthorizationException::class, $exception);
        }

        $this->assertDatabaseCount('cargos', 1);
        $this->assertSame($before, $existing->fresh()->getRawOriginal());
    }

    public static function unauthorizedSensitiveCalls(): array
    {
        $cases = [];
        foreach (['venta', 'profe', 'alum', 'invalid'] as $role) {
            foreach (['mount', 'create', 'closeForm', 'store', 'order', 'updating', 'render'] as $method) {
                $cases["{$role} {$method}"] = [$role, $method];
            }
        }
        return $cases;
    }

    public function test_filter_and_manual_concept_collections_have_distinct_rules(): void
    {
        foreach (['RECARGO', 'DESCUENTO'] as $key) ConceptoCobro::create(['clave' => $key, 'nombre' => $key, 'activo' => true]);
        $inactive = ConceptoCobro::create(['clave' => 'ARCHIVADO', 'nombre' => 'Archivado', 'activo' => false]);
        $this->actingAs($this->user('admin'));

        Livewire::test(ShowCargos::class)
            ->assertViewHas('conceptosFiltro', function ($items) {
                $keys = $items->pluck('clave')->all();
                return in_array('INSCRIPCION', $keys, true) && in_array('MENSUALIDAD', $keys, true) && in_array('MATERIAL', $keys, true);
            })
            ->assertViewHas('conceptosManuales', function ($items) {
                $keys = $items->pluck('clave')->all();
                return in_array('MATERIAL', $keys, true) && count(array_intersect(CreadorCargoManualService::CONCEPTOS_RESERVADOS, $keys)) === 0;
            });

        Livewire::test(ShowCargos::class)->call('create')->set('inscripciones_id', $this->inscripcion->getKey())
            ->set('concepto_cobro_id', $inactive->getKey())->set('fecha_emision', '2026-01-01')
            ->set('fecha_vencimiento', '2026-01-02')->set('subtotal', '10.00')->call('store')
            ->assertHasErrors(['concepto_cobro_id']);
        $this->assertDatabaseCount('cargos', 0);
    }

    public function test_form_opens_with_safe_defaults_and_only_active_allowed_concepts(): void
    {
        $this->actingAs($this->user('admin'));
        ConceptoCobro::create(['clave' => 'INACTIVO', 'nombre' => 'Inactivo', 'activo' => false]);
        Livewire::test(ShowCargos::class)
            ->call('create')
            ->assertSet('open_form', true)
            ->assertSet('busqueda_inscripcion', '')
            ->assertSet('inscripciones_id', '')
            ->assertSet('concepto_cobro_id', '')
            ->assertSet('fecha_emision', now()->toDateString())
            ->assertSet('fecha_vencimiento', now()->toDateString())
            ->assertSet('subtotal', '')
            ->assertSet('periodo_anio', '')
            ->assertSet('periodo_mes', '')
            ->assertSet('observaciones', '')
            ->assertViewHas('conceptosManuales', function ($items) {
                $keys = $items->pluck('clave')->all();

                return in_array('MATERIAL', $keys, true)
                    && count(array_intersect(CreadorCargoManualService::CONCEPTOS_RESERVADOS, $keys)) === 0
                    && ! in_array('INACTIVO', $keys, true);
            })
            ->set('busqueda_inscripcion', 'Alumno temporal')
            ->set('inscripciones_id', $this->inscripcion->getKey())
            ->set('concepto_cobro_id', $this->concepto->getKey())
            ->set('subtotal', '10.00')
            ->set('periodo_anio', '2026')
            ->set('periodo_mes', '9')
            ->set('observaciones', 'Temporal')
            ->call('closeForm')
            ->assertSet('open_form', false)
            ->assertSet('busqueda_inscripcion', '')
            ->assertSet('inscripciones_id', '')
            ->assertSet('concepto_cobro_id', '')
            ->assertSet('fecha_emision', '')
            ->assertSet('fecha_vencimiento', '')
            ->assertSet('subtotal', '')
            ->assertSet('periodo_anio', '')
            ->assertSet('periodo_mes', '')
            ->assertSet('observaciones', '');
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

    /** @dataProvider individualFilters */
    public function test_each_list_filter_returns_exact_rows(string $case): void
    {
        [$cargos, $conceptos] = $this->filterFixture();
        $values = [
            'id' => ['search', (string) $cargos[0]->getKey(), [0]],
            'observaciones' => ['search', 'REF-BRUNO-ONLY', [1]],
            'clave concepto' => ['search', 'EXAMEN-UNICO', [2]],
            'nombre concepto' => ['search', 'Libro exclusivo', [1]],
            'nombre alumno' => ['search', 'CarlaUnica', [2]],
            'apellido alumno' => ['search', 'BetaUnico', [1]],
            'nombre completo' => ['search', 'AliceUnica AlphaUnico', [0]],
            'estado' => ['estado', Cargo::ESTADO_PAGADO, [1]],
            'origen' => ['origen', Cargo::ORIGEN_AUTOMATICO, [1]],
            'concepto' => ['concepto', (string) $conceptos[2]->getKey(), [2]],
            'anio' => ['periodo_anio_filtro', '2027', [1]],
            'mes' => ['periodo_mes_filtro', '2', [0]],
            'desde' => ['vencimiento_desde', '2026-03-01', [1, 2]],
            'hasta' => ['vencimiento_hasta', '2026-02-28', [0]],
        ];
        [$property, $value, $indexes] = $values[$case];
        $expected = array_map(fn ($index) => $cargos[$index]->getKey(), $indexes);
        sort($expected);
        $this->actingAs($this->user('admin'));

        Livewire::test(ShowCargos::class)->set($property, $value)->assertViewHas('cargos', function ($items) use ($expected) {
            $actual = $items->getCollection()->pluck('cargo_id')->all();
            sort($actual);
            return $actual === $expected && $items->total() === count($expected);
        });
    }

    public static function individualFilters(): array
    {
        return array_combine(array_map(fn ($case) => "filter {$case}", ['id', 'observaciones', 'clave concepto', 'nombre concepto', 'nombre alumno', 'apellido alumno', 'nombre completo', 'estado', 'origen', 'concepto', 'anio', 'mes', 'desde', 'hasta']),
            array_map(fn ($case) => [$case], ['id', 'observaciones', 'clave concepto', 'nombre concepto', 'nombre alumno', 'apellido alumno', 'nombre completo', 'estado', 'origen', 'concepto', 'anio', 'mes', 'desde', 'hasta']));
    }

    public function test_full_due_date_range_and_combined_filters_return_only_the_exact_charge(): void
    {
        [$cargos, $conceptos] = $this->filterFixture();
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowCargos::class)->set('estado', Cargo::ESTADO_PENDIENTE)->set('origen', Cargo::ORIGEN_MANUAL)
            ->set('concepto', (string) $conceptos[0]->getKey())->set('periodo_anio_filtro', '2026')->set('periodo_mes_filtro', '2')
            ->set('vencimiento_desde', '2026-02-01')->set('vencimiento_hasta', '2026-02-28')
            ->assertViewHas('cargos', fn ($items) => $items->total() === 1 && $items->first()->is($cargos[0]));
    }

    public function test_page_size_and_deterministic_sort_direction_are_applied(): void
    {
        [$cargos] = $this->filterFixture();
        foreach (range(1, 9) as $index) Cargo::create($this->cargoAttributes(['fecha_vencimiento' => '2027-01-01', 'observaciones' => "extra {$index}"]));
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowCargos::class)->set('cant', 10)->assertViewHas('cargos', fn ($items) => $items->perPage() === 10 && $items->count() === 10)
            ->call('order', 'cargo_id')->assertViewHas('cargos', fn ($items) => $items->pluck('cargo_id')->all() === range(1, 10))
            ->call('order', 'cargo_id')->assertViewHas('cargos', fn ($items) => $items->pluck('cargo_id')->all() === range(12, 3));
    }

    /** @dataProvider paginationResetFilters */
    public function test_each_filter_change_really_resets_pagination(string $property, $value): void
    {
        foreach (range(1, 30) as $index) Cargo::create($this->cargoAttributes(['observaciones' => "cargo {$index}"]));
        $this->actingAs($this->user('admin'));
        Livewire::test(ShowCargos::class)->call('gotoPage', 2)->assertSet('page', 2)->set($property, $value)->assertSet('page', 1);
    }

    public static function paginationResetFilters(): array
    {
        return [['search', 'cargo'], ['estado', 'pendiente'], ['origen', 'manual'], ['concepto', '1'],
            ['periodo_anio_filtro', '2026'], ['periodo_mes_filtro', '2'], ['vencimiento_desde', '2026-01-01'],
            ['vencimiento_hasta', '2026-12-31'], ['cant', 10]];
    }

    /** @dataProvider manipulatedFilters */
    public function test_manipulated_filters_are_ignored_or_normalized_without_sql_or_writes(string $property, $value): void
    {
        $cargo = Cargo::create($this->cargoAttributes(['observaciones' => 'intocable']));
        $original = $cargo->fresh()->getRawOriginal();
        $this->actingAs($this->user('admin'));
        $component = Livewire::test(ShowCargos::class)->set($property, $value)
            ->assertViewHas('cargos', fn ($items) => $items->total() === 1 && $items->first()->is($cargo));
        if (in_array($property, ['sort', 'direction'], true)) $component->assertSet('sort', 'fecha_vencimiento')->assertSet('direction', 'desc');
        if ($property === 'cant') $component->assertViewHas('cargos', fn ($items) => $items->perPage() === 25);
        foreach (['users', 'cargos', 'inscripciones', 'conceptos_cobro'] as $table) $this->assertTrue(Schema::hasTable($table));
        $this->assertSame($original, $cargo->fresh()->getRawOriginal());
    }

    public static function manipulatedFilters(): array
    {
        return [
            ['estado', 'desconocido'], ['origen', 'hack'], ['concepto', "1); DROP TABLE cargos;--"],
            ['periodo_anio_filtro', "2026 OR 1=1"], ['periodo_mes_filtro', '99'],
            ['vencimiento_desde', '2026-02-30'], ['vencimiento_desde', '2026-13-01'], ['vencimiento_desde', '2026-00-10'],
            ['vencimiento_hasta', '2026-99-99'], ['vencimiento_hasta', '01/02/2026'], ['vencimiento_hasta', "2026-01-01' OR 1=1--"],
            ['sort', 'cargo_id; DROP TABLE users'], ['direction', 'sideways'], ['cant', -1], ['cant', 0], ['cant', 'texto'],
            ['cant', '10abc'], ['cant', 1000000],
        ];
    }

    private function filterFixture(): array
    {
        $this->inscripcion->prospecto->update(['prospectos_nombres' => 'AliceUnica', 'prospectos_apellidos' => 'AlphaUnico']);
        [$p2, $curso2, $grupo2] = $this->catalogs(); $p2->update(['prospectos_nombres' => 'BrunoUnico', 'prospectos_apellidos' => 'BetaUnico']);
        [$p3, $curso3, $grupo3] = $this->catalogs(); $p3->update(['prospectos_nombres' => 'CarlaUnica', 'prospectos_apellidos' => 'GammaUnico']);
        $inscripciones = [$this->inscripcion, $this->enroll($p2, $curso2, $grupo2), $this->enroll($p3, $curso3, $grupo3)];
        $conceptos = [$this->concepto, ConceptoCobro::create(['clave' => 'LIBRO-UNICO', 'nombre' => 'Libro exclusivo', 'activo' => true]),
            ConceptoCobro::create(['clave' => 'EXAMEN-UNICO', 'nombre' => 'Evaluación exclusiva', 'activo' => true])];
        $changes = [
            ['estado' => 'pendiente', 'origen' => 'manual', 'periodo_anio' => 2026, 'periodo_mes' => 2, 'fecha_vencimiento' => '2026-02-10', 'observaciones' => 'REF-ALICE-ONLY'],
            ['estado' => 'pagado', 'origen' => 'automatico', 'periodo_anio' => 2027, 'periodo_mes' => 3, 'fecha_vencimiento' => '2026-03-20', 'observaciones' => 'REF-BRUNO-ONLY'],
            ['estado' => 'parcial', 'origen' => 'manual', 'periodo_anio' => 2026, 'periodo_mes' => 3, 'fecha_vencimiento' => '2026-04-30', 'observaciones' => 'REF-CARLA-ONLY'],
        ];
        $cargos = [];
        foreach ($changes as $index => $change) $cargos[] = Cargo::create($this->cargoAttributes($change + ['inscripciones_id' => $inscripciones[$index]->getKey(), 'concepto_cobro_id' => $conceptos[$index]->getKey()]));
        return [$cargos, $conceptos];
    }

    private function cargoAttributes(array $changes = []): array
    {
        return array_merge(['inscripciones_id' => $this->inscripcion->getKey(), 'concepto_cobro_id' => $this->concepto->getKey(),
            'fecha_emision' => '2026-01-01', 'fecha_vencimiento' => '2026-02-01', 'moneda' => 'MXN', 'subtotal' => '10.00',
            'descuento' => '0.00', 'recargo' => '0.00', 'impuestos' => '0.00', 'total' => '10.00', 'saldo_pendiente' => '10.00',
            'estado' => 'pendiente', 'origen' => 'manual'], $changes);
    }
}
