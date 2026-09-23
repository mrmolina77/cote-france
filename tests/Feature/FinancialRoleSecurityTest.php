<?php

namespace Tests\Feature;

use App\Http\Livewire\EstadoCuenta;
use App\Http\Livewire\RegistrarPago;
use App\Http\Livewire\ShowAuditoriaPagos;
use App\Http\Livewire\ShowCargos;
use App\Http\Livewire\ShowCobranza;
use App\Http\Livewire\ShowConceptosCobro;
use App\Http\Livewire\ShowInscripciones;
use App\Http\Livewire\ShowMetodosPago;
use App\Http\Livewire\ShowPagos;
use App\Models\Role;
use App\Models\User;
use App\Support\FinancialPermissions;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

class FinancialRoleSecurityTest extends InscripcionesTestCase
{
    /** @dataProvider permissionMatrix */
    public function test_central_financial_permission_matrix(string $role, array $allowed): void
    {
        $user = $this->user($role);
        foreach ($this->permissions() as $permission) {
            $this->assertSame(in_array($permission, $allowed, true), Gate::forUser($user)->allows($permission), $role.' / '.$permission);
        }
    }

    public function permissionMatrix(): array
    {
        return [
            'admin' => ['admin', $this->permissions()],
            'caja' => ['caja', ['view-financial', 'register-payments', 'view-payment-documents', 'manage-pagos']],
            'contabilidad' => ['contabilidad', ['view-financial', 'view-payment-documents', 'audit-payments', 'view-financial-enrollments', 'cancel-pagos']],
            'venta' => ['venta', ['view-financial']],
            'profe' => ['profe', []],
            'alum' => ['alum', []],
            'unknown' => ['desconocido', []],
        ];
    }

    public function test_missing_role_is_denied_by_default(): void
    {
        $user = User::factory()->create(['roles_id' => 999999]);
        foreach ($this->permissions() as $permission) {
            $this->assertFalse(Gate::forUser($user)->allows($permission));
        }
    }

    public function test_explicitly_null_role_relation_is_denied_by_helper_and_every_gate(): void
    {
        $user = User::factory()->create(['roles_id' => 999999]);
        $user->setRelation('role', null);

        foreach ($this->permissions() as $permission) {
            $this->assertFalse(FinancialPermissions::allows($user, $permission), 'helper / '.$permission);
            $this->assertFalse(Gate::forUser($user)->allows($permission), 'gate / '.$permission);
        }
    }

    /** @dataProvider routeAccessMatrix */
    public function test_financial_routes_enforce_the_complete_role_matrix(string $route, array $allowedRoles): void
    {
        $this->get(route($route))->assertRedirect('/login');

        foreach (['admin', 'caja', 'contabilidad', 'venta', 'profe', 'alum', 'desconocido'] as $role) {
            $response = $this->actingAs($this->user($role))->get(route($route));
            $response->assertStatus(in_array($role, $allowedRoles, true) ? 200 : 403);
        }

        $withoutRole = User::factory()->create(['roles_id' => 999999]);
        $this->actingAs($withoutRole)->get(route($route))->assertForbidden();
    }

    public function routeAccessMatrix(): array
    {
        $read = ['admin', 'caja', 'contabilidad', 'venta'];

        return [
            'cobranza' => ['facturacion.cobranza', $read],
            'estado de cuenta' => ['facturacion.estado-cuenta', $read],
            'pagos' => ['facturacion.pagos.index', $read],
            'registrar pago' => ['facturacion.pagos.registrar', ['admin', 'caja']],
            'inscripciones financieras' => ['inscripciones', ['admin', 'contabilidad']],
            'cargos' => ['facturacion.cargos', ['admin']],
            'conceptos' => ['configuracion.conceptos-cobro', ['admin']],
            'metodos' => ['configuracion.metodos-pago', ['admin']],
            'auditoria' => ['facturacion.auditoria', ['admin', 'contabilidad']],
        ];
    }

    /** @dataProvider componentAccessMatrix */
    public function test_livewire_mounts_enforce_the_complete_role_matrix(string $component, array $allowedRoles): void
    {
        foreach (['admin', 'caja', 'contabilidad', 'venta', 'profe', 'alum', 'desconocido'] as $role) {
            $test = Livewire::actingAs($this->user($role))->test($component);
            in_array($role, $allowedRoles, true) ? $test->assertOk() : $test->assertForbidden();
        }

        $withoutRole = User::factory()->create(['roles_id' => 999999]);
        Livewire::actingAs($withoutRole)->test($component)->assertForbidden();
    }

    public function componentAccessMatrix(): array
    {
        $read = ['admin', 'caja', 'contabilidad', 'venta'];

        return [
            'cobranza' => [ShowCobranza::class, $read],
            'estado de cuenta' => [EstadoCuenta::class, $read],
            'pagos' => [ShowPagos::class, $read],
            'registrar pago' => [RegistrarPago::class, ['admin', 'caja']],
            'auditoria' => [ShowAuditoriaPagos::class, ['admin', 'contabilidad']],
            'inscripciones' => [ShowInscripciones::class, ['admin', 'contabilidad']],
            'cargos' => [ShowCargos::class, ['admin']],
            'conceptos' => [ShowConceptosCobro::class, ['admin']],
            'metodos' => [ShowMetodosPago::class, ['admin']],
        ];
    }

    /** @dataProvider financialMenuViews */
    public function test_desktop_and_mobile_financial_links_follow_permissions(string $view): void
    {
        $expectations = [
            'admin' => ['facturacion.cobranza', 'facturacion.estado-cuenta', 'facturacion.pagos.index', 'facturacion.pagos.registrar', 'inscripciones', 'facturacion.cargos', 'configuracion.conceptos-cobro', 'configuracion.metodos-pago', 'facturacion.auditoria'],
            'caja' => ['facturacion.cobranza', 'facturacion.estado-cuenta', 'facturacion.pagos.index', 'facturacion.pagos.registrar'],
            'contabilidad' => ['facturacion.cobranza', 'facturacion.estado-cuenta', 'facturacion.pagos.index', 'inscripciones', 'facturacion.auditoria'],
            'venta' => ['facturacion.cobranza', 'facturacion.estado-cuenta', 'facturacion.pagos.index'],
            'profe' => [], 'alum' => [], 'desconocido' => [],
        ];
        $financialRoutes = array_values(array_unique(array_merge(...array_values($expectations))));

        foreach ($expectations as $role => $visible) {
            $this->actingAs($this->user($role));
            $html = view($view)->render();
            foreach ($financialRoutes as $route) {
                $assertion = in_array($route, $visible, true) ? 'assertStringContainsString' : 'assertStringNotContainsString';
                $this->{$assertion}(route($route), $html, $role.' / '.$route);
            }
        }

        $withoutRole = User::factory()->create(['roles_id' => 999999]);
        $this->actingAs($withoutRole);
        $html = view($view)->render();
        foreach ($financialRoutes as $route) {
            $this->assertStringNotContainsString(route($route), $html, 'sin rol / '.$route);
        }
    }

    public function financialMenuViews(): array
    {
        return [['components.layout.aside'], ['components.layout.mobile-header']];
    }

    public function test_role_seeder_is_idempotent_and_preserves_existing_rows(): void
    {
        $existing = Role::create(['roles_codigo' => 'admin', 'roles_nombre' => 'Nombre existente']);
        $originalId = $existing->getKey();

        (new RoleSeeder())->run();
        $counts = Role::query()->selectRaw('roles_codigo, COUNT(*) total')->groupBy('roles_codigo')->pluck('total', 'roles_codigo')->all();
        (new RoleSeeder())->run();

        $this->assertSame($originalId, Role::where('roles_codigo', 'admin')->value('roles_id'));
        $this->assertSame('Nombre existente', Role::where('roles_codigo', 'admin')->value('roles_nombre'));
        $this->assertSame($counts, Role::query()->selectRaw('roles_codigo, COUNT(*) total')->groupBy('roles_codigo')->pluck('total', 'roles_codigo')->all());
        $this->assertSame('Caja / Cobranza', Role::where('roles_codigo', 'caja')->value('roles_nombre'));
        $this->assertSame('Contabilidad', Role::where('roles_codigo', 'contabilidad')->value('roles_nombre'));
    }

    private function permissions(): array
    {
        return [
            'view-financial', 'register-payments', 'view-payment-documents', 'audit-payments',
            'view-financial-enrollments', 'manage-inscripciones', 'manage-conceptos-cobro',
            'manage-metodos-pago', 'manage-cargos', 'manage-pagos', 'cancel-pagos',
        ];
    }
}
