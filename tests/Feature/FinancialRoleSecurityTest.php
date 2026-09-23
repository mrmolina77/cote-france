<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Gate;

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
