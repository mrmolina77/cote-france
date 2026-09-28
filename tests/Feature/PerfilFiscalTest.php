<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowPerfilesFiscales;
use App\Models\PerfilFiscal;
use App\Models\Prospecto;
use App\Models\Role;
use App\Models\User;
use App\Services\Facturacion\PerfilFiscalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PerfilFiscalTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_are_centralized_and_route_is_protected(): void
    {
        foreach (['admin', 'contabilidad'] as $role) {
            $user = $this->user($role);
            $this->assertTrue(Gate::forUser($user)->allows('manage-fiscal-profiles'));
            $this->actingAs($user)->get('/configuracion/perfiles-fiscales')->assertOk();
        }
        foreach (['caja', 'venta', 'profe', 'desconocido'] as $role) {
            $user = $this->user($role);
            $this->assertFalse(Gate::forUser($user)->allows('manage-fiscal-profiles'));
            $this->actingAs($user)->get('/configuracion/perfiles-fiscales')->assertForbidden();
        }
        $withoutRole = User::factory()->create(['roles_id' => null]);
        $this->actingAs($withoutRole)->get('/configuracion/perfiles-fiscales')->assertForbidden();
        $this->app['auth']->forgetGuards(); $this->get('/configuracion/perfiles-fiscales')->assertRedirect('/login');
    }

    public function test_profiles_are_normalized_and_only_one_is_default(): void
    {
        $admin = $this->user('admin'); $alumno = $this->alumno(); $service = app(PerfilFiscalService::class);
        $first = $service->guardar($this->valid($alumno, ['rfc' => ' aaaa010101aa1 ', 'predeterminado' => true]), $admin->id);
        $second = $service->guardar($this->valid($alumno, ['rfc' => 'BBBB010101BB2', 'predeterminado' => true]), $admin->id);
        $this->assertSame('AAAA010101AA1', $first->fresh()->rfc);
        $this->assertFalse($first->fresh()->predeterminado); $this->assertTrue($second->fresh()->predeterminado);
        $service->cambiarEstado($second, false, $admin->id);
        $this->assertFalse($second->fresh()->predeterminado);
    }

    /** @dataProvider invalidFormats */
    public function test_structural_formats_are_rejected(string $field, string $value): void
    {
        $this->expectException(ValidationException::class);
        app(PerfilFiscalService::class)->guardar($this->valid($this->alumno(), [$field => $value]), $this->user('admin')->id);
    }

    public static function invalidFormats(): array
    {
        return [['rfc', 'RFC-MALO'], ['curp', 'CURP-MALA'], ['codigo_postal_fiscal', '1234'], ['correo_facturacion', 'no-es-correo']];
    }

    public function test_livewire_rejects_a_profile_from_another_student(): void
    {
        $admin = $this->user('admin'); $a = $this->alumno(); $b = $this->alumno();
        $profile = app(PerfilFiscalService::class)->guardar($this->valid($b), $admin->id);
        Livewire::actingAs($admin)->test(ShowPerfilesFiscales::class)->set('alumnoId', $a->getKey())->call('edit', $profile->getKey())->assertNotFound();
    }

    private function valid(Prospecto $p, array $changes = []): array
    {
        return array_merge(['prospectos_id'=>$p->getKey(),'tipo_persona'=>'fisica','rfc'=>'AAAA010101AA1','nombre_razon_social'=>'Receptor','codigo_postal_fiscal'=>'01000','regimen_fiscal'=>'605','uso_cfdi'=>'D10','correo_facturacion'=>'FACTURA@EXAMPLE.COM','relacion_alumno'=>'Madre','curp'=>'AAAA010101MDFBBB01','nivel_educativo'=>'Licenciatura','rvoe'=>'RVOE-1','predeterminado'=>false,'activo'=>true,'fecha_validacion'=>'2026-09-28'], $changes);
    }

    private function alumno(): Prospecto { return Prospecto::create(['prospectos_nombres'=>'Ana','prospectos_apellidos'=>uniqid('Alumno'),'prospectos_telefono1'=>'5555555555']); }
    private function user(string $code): User
    {
        $role = Role::firstOrCreate(['roles_codigo'=>$code], ['roles_nombre'=>$code]);
        return User::factory()->create(['roles_id'=>$role->getKey(), 'email_verified_at'=>now()]);
    }
}
