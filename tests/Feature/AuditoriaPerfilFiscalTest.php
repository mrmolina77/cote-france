<?php

namespace Tests\Feature;

use App\Models\AuditoriaPerfilFiscal;
use App\Models\PerfilFiscal;
use App\Models\Prospecto;
use App\Models\Role;
use App\Models\User;
use App\Services\Facturacion\AuditoriaPerfilFiscalService;
use App\Services\Facturacion\PerfilFiscalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AuditoriaPerfilFiscalTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_update_default_and_status_events_record_actor_and_only_field_names(): void
    {
        $actor = $this->user();
        $alumno = $this->alumno();
        $service = app(PerfilFiscalService::class);
        $first = $service->guardar($this->valid($alumno), $actor->id);

        $created = AuditoriaPerfilFiscal::query()->sole();
        $this->assertSame(AuditoriaPerfilFiscal::CREAR, $created->accion);
        $this->assertSame($actor->id, $created->usuario_id);
        $this->assertSame($alumno->getKey(), $created->prospectos_id);
        $this->assertContains('rfc', $created->campos_modificados);

        $service->guardar($this->valid($alumno, ['nombre_razon_social' => 'Nombre cambiado']), $actor->id, $first);
        $updated = AuditoriaPerfilFiscal::query()->where('accion', AuditoriaPerfilFiscal::ACTUALIZAR)->latest('auditoria_perfil_fiscal_id')->firstOrFail();
        $this->assertSame(['nombre_razon_social'], $updated->campos_modificados);

        $second = $service->guardar($this->valid($alumno, ['rfc' => 'BBBB010101BB2', 'predeterminado' => true]), $actor->id);
        $service->guardar($this->valid($alumno, ['predeterminado' => true, 'nombre_razon_social' => 'Nombre cambiado']), $actor->id, $first->fresh());
        $this->assertFalse($second->fresh()->predeterminado);
        $this->assertDatabaseHas('auditoria_perfiles_fiscales', ['perfil_fiscal_id' => $second->getKey(), 'accion' => AuditoriaPerfilFiscal::PREDETERMINAR]);

        $service->cambiarEstado($first->fresh(), false, $actor->id);
        $service->cambiarEstado($first->fresh(), true, $actor->id);
        $this->assertDatabaseHas('auditoria_perfiles_fiscales', ['perfil_fiscal_id' => $first->getKey(), 'accion' => AuditoriaPerfilFiscal::DESACTIVAR]);
        $this->assertDatabaseHas('auditoria_perfiles_fiscales', ['perfil_fiscal_id' => $first->getKey(), 'accion' => AuditoriaPerfilFiscal::ACTIVAR]);

        $serialized = AuditoriaPerfilFiscal::query()->get()->toJson();
        foreach (['AAAA010101AA1', 'AAAA010101MDFBBB01', 'FACTURA@EXAMPLE.COM', 'Receptor'] as $sensitive) {
            $this->assertStringNotContainsString($sensitive, $serialized);
        }
    }

    public function test_profile_write_rolls_back_when_audit_write_fails(): void
    {
        $auditoria = Mockery::mock(AuditoriaPerfilFiscalService::class);
        $auditoria->shouldReceive('registrar')->once()->andThrow(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditoriaPerfilFiscalService::class, $auditoria);

        try {
            app(PerfilFiscalService::class)->guardar($this->valid($this->alumno()), $this->user()->id);
            $this->fail('La escritura debía fallar.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }

        $this->assertDatabaseCount('perfiles_fiscales', 0);
        $this->assertDatabaseCount('auditoria_perfiles_fiscales', 0);
    }

    public function test_audit_events_are_append_only_through_the_model(): void
    {
        $perfil = app(PerfilFiscalService::class)->guardar($this->valid($this->alumno()), $this->user()->id);
        $evento = $perfil->auditorias()->sole();

        foreach ([fn () => $evento->update(['accion' => 'alterada']), fn () => $evento->delete(), fn () => $evento->save()] as $operation) {
            try { $operation(); $this->fail('El evento debía ser inmutable.'); } catch (LogicException $exception) {
                $this->assertStringContainsString('auditoría', $exception->getMessage());
            }
        }
        $this->assertDatabaseCount('auditoria_perfiles_fiscales', 1);
    }

    private function valid(Prospecto $alumno, array $changes = []): array
    {
        return array_merge(['prospectos_id'=>$alumno->getKey(),'tipo_persona'=>'fisica','rfc'=>'AAAA010101AA1',
            'nombre_razon_social'=>'Receptor','codigo_postal_fiscal'=>'01000','regimen_fiscal'=>'605','uso_cfdi'=>'D10',
            'correo_facturacion'=>'FACTURA@EXAMPLE.COM','relacion_alumno'=>'Madre','curp'=>'AAAA010101MDFBBB01',
            'nivel_educativo'=>'Licenciatura','rvoe'=>'RVOE-1','predeterminado'=>false,'activo'=>true,
            'fecha_validacion'=>'2026-09-28'], $changes);
    }

    private function alumno(): Prospecto
    {
        return Prospecto::create(['prospectos_nombres'=>'Ana','prospectos_apellidos'=>uniqid('Alumno'), 'prospectos_telefono1'=>'5555555555']);
    }

    private function user(): User
    {
        $role = Role::firstOrCreate(['roles_codigo'=>'admin'], ['roles_nombre'=>'Admin']);
        return User::factory()->create(['roles_id'=>$role->getKey(), 'email_verified_at'=>now()]);
    }
}
