<?php

namespace Tests\Feature;

use App\Http\Livewire\CreateInscripciones;
use App\Http\Livewire\CreateProspect;
use App\Http\Livewire\ShowInscripciones;
use App\Models\Curso;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Prospecto;
use App\Models\ResponsablePago;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class InscripcionesTest extends InscripcionesTestCase
{
    public function test_admin_can_access_inscripciones_and_other_roles_receive_403(): void
    {
        $this->actingAs($this->user('admin'))->get('/inscripciones')->assertOk();

        foreach (['venta', 'profe', 'alum'] as $role) {
            $this->actingAs($this->user($role))->get('/inscripciones')->assertForbidden();
        }
    }

    public function test_generic_factory_user_is_not_authorized_to_manage_inscripciones(): void
    {
        $user = User::factory()->create();

        $this->assertSame('venta', $user->role->roles_codigo);
        $this->assertFalse(Gate::forUser($user)->allows('manage-inscripciones'));
    }

    public function test_valid_inscripcion_can_be_created(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();

        Livewire::actingAs($this->user('admin'))
            ->test(CreateInscripciones::class)
            ->set('fecha_inscripcion', '2026-08-16')
            ->set('prospectos_id', $prospecto->getKey())
            ->set('cursos_id', $curso->getKey())
            ->set('grupo_id', $grupo->getKey())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inscripciones', ['prospectos_id' => $prospecto->getKey()]);
    }

    public function test_financial_enrollment_creates_student_responsible_and_audit_data(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $user = $this->user('admin');

        Livewire::actingAs($user)->test(CreateInscripciones::class)
            ->set('prospectos_id', $prospecto->getKey())->set('cursos_id', $curso->getKey())->set('grupo_id', $grupo->getKey())
            ->set('monto_inscripcion', '1250.50')->set('monto_mensualidad', '2500')
            ->set('dia_vencimiento', 10)->set('numero_mensualidades', 6)->set('descuento', '5.25')->set('beca', '10.00')
            ->call('save')->assertHasNoErrors()->assertSet('open', false);

        $responsable = ResponsablePago::where('prospectos_id', $prospecto->getKey())->firstOrFail();
        $this->assertDatabaseHas('inscripciones', ['moneda' => 'MXN', 'estatus' => 'activa', 'monto_inscripcion' => 1250.50,
            'monto_mensualidad' => 2500.00, 'responsable_pago_id' => $responsable->getKey(), 'created_by' => $user->getKey(), 'updated_by' => $user->getKey()]);
    }

    public function test_existing_inactive_responsible_is_rejected(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $responsable = ResponsablePago::create(['tipo'=>'persona','nombre_razon_social'=>'Inactivo','activo'=>false]);
        Livewire::actingAs($this->user('admin'))->test(CreateInscripciones::class)
            ->set('prospectos_id',$prospecto->getKey())->set('cursos_id',$curso->getKey())->set('grupo_id',$grupo->getKey())
            ->set('responsable_opcion','existente')->set('responsable_pago_id',$responsable->getKey())->call('save')
            ->assertHasErrors(['responsable_pago_id']);
        $this->assertDatabaseCount('inscripciones', 0);
    }

    public function test_monthly_amount_requires_due_day_and_installment_count(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        Livewire::actingAs($this->user('admin'))->test(CreateInscripciones::class)
            ->set('prospectos_id',$prospecto->getKey())->set('cursos_id',$curso->getKey())->set('grupo_id',$grupo->getKey())
            ->set('monto_mensualidad','100.00')->call('save')
            ->assertHasErrors(['dia_vencimiento'=>'required','numero_mensualidades'=>'required']);
    }

    /** @dataProvider installmentCounts */
    public function test_installment_count_has_a_business_limit($count, ?string $rule): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $component = Livewire::actingAs($this->user('admin'))->test(CreateInscripciones::class)
            ->set('prospectos_id', $prospecto->getKey())->set('cursos_id', $curso->getKey())->set('grupo_id', $grupo->getKey())
            ->set('monto_mensualidad', 100)->set('dia_vencimiento', 15)->set('numero_mensualidades', $count)->call('save');

        $rule ? $component->assertHasErrors(['numero_mensualidades' => $rule]) : $component->assertHasNoErrors();
    }

    public function installmentCounts(): array
    {
        return ['120 is valid' => [120, null], '121 exceeds max' => [121, 'max'], 'decimal is invalid' => [12.5, 'integer']];
    }

    /** @dataProvider discountScholarshipCombinations */
    public function test_discount_plus_scholarship_is_validated_on_creation($discount, $scholarship, bool $valid): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $component = Livewire::actingAs($this->user('admin'))->test(CreateInscripciones::class)
            ->set('prospectos_id', $prospecto->getKey())->set('cursos_id', $curso->getKey())->set('grupo_id', $grupo->getKey())
            ->set('descuento', $discount)->set('beca', $scholarship)->call('save');
        $valid ? $component->assertHasNoErrors() : $component->assertHasErrors(['beca']);
    }

    public function discountScholarshipCombinations(): array
    {
        return [
            'exactly 100' => [40, 60, true], 'over 100' => [60, 41, false],
            'blank discount' => ['', 25, true], 'blank scholarship' => [25, '', true],
        ];
    }

    public function test_discount_plus_scholarship_is_validated_on_edit(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        Livewire::actingAs($this->user('admin'))->test(ShowInscripciones::class)->call('edit', $inscripcion->getKey())
            ->set('inscripcion.descuento', 60)->set('inscripcion.beca', 41)->call('update')
            ->assertHasErrors(['inscripcion.beca']);
        $this->assertNull($inscripcion->fresh()->descuento);
    }

    public function test_creation_rejects_manipulated_conservar_option(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        Livewire::actingAs($this->user('admin'))->test(CreateInscripciones::class)
            ->set('prospectos_id', $prospecto->getKey())->set('cursos_id', $curso->getKey())->set('grupo_id', $grupo->getKey())
            ->set('responsable_opcion', 'conservar')->call('save')->assertHasErrors(['responsable_opcion' => 'in']);
        $this->assertDatabaseCount('inscripciones', 0);
    }

    public function test_creation_supports_existing_new_and_reused_student_responsibles(): void
    {
        [$prospectoA, $curso, $grupo] = $this->catalogs();
        $existing = ResponsablePago::create(['tipo'=>'persona','nombre_razon_social'=>'Tutor','activo'=>true]);
        Livewire::actingAs($this->user('admin'))->test(CreateInscripciones::class)
            ->set('prospectos_id',$prospectoA->getKey())->set('cursos_id',$curso->getKey())->set('grupo_id',$grupo->getKey())
            ->set('responsable_opcion','existente')->set('responsable_pago_id',$existing->getKey())->call('save')->assertHasNoErrors();
        $this->assertSame($existing->getKey(), Inscripcion::where('prospectos_id',$prospectoA->getKey())->value('responsable_pago_id'));

        $prospectoB = Prospecto::create(['prospectos_nombres'=>'B Alumno','prospectos_telefono1'=>'2']);
        Livewire::actingAs($this->user('admin'))->test(CreateInscripciones::class)
            ->set('prospectos_id',$prospectoB->getKey())->set('cursos_id',$curso->getKey())->set('grupo_id',$grupo->getKey())
            ->set('responsable_opcion','nuevo')->set('responsable_nombre','Nuevo tutor')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('responsables_pago', ['nombre_razon_social'=>'Nuevo tutor']);

        $prospectoC = Prospecto::create(['prospectos_nombres'=>'C Alumno','prospectos_telefono1'=>'3']);
        $studentResponsible = ResponsablePago::activeForProspect($prospectoC);
        $before = ResponsablePago::count();
        Livewire::actingAs($this->user('admin'))->test(CreateInscripciones::class)
            ->set('prospectos_id',$prospectoC->getKey())->set('cursos_id',$curso->getKey())->set('grupo_id',$grupo->getKey())->call('save')->assertHasNoErrors();
        $this->assertSame($before, ResponsablePago::count());
        $this->assertSame($studentResponsible->getKey(), Inscripcion::where('prospectos_id',$prospectoC->getKey())->value('responsable_pago_id'));
    }

    public function test_failed_enrollment_validation_does_not_leave_an_orphan_responsible(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        Livewire::actingAs($this->user('admin'))->test(CreateInscripciones::class)
            ->set('prospectos_id',$prospecto->getKey())->set('cursos_id',$curso->getKey())->set('grupo_id',$grupo->getKey())
            ->set('responsable_opcion','nuevo')->set('responsable_nombre','Tutor temporal')
            ->set('descuento',60)->set('beca',41)->call('save')->assertHasErrors(['beca']);

        $this->assertDatabaseCount('responsables_pago', 0);
        $this->assertDatabaseCount('inscripciones', 0);
    }

    /** @dataProvider invalidCreationData */
    public function test_creation_rejects_missing_date_and_nonexistent_ids(string $property, $value, string $rule): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $values = [
            'fecha_inscripcion' => '2026-08-16',
            'prospectos_id' => $prospecto->getKey(),
            'cursos_id' => $curso->getKey(),
            'grupo_id' => $grupo->getKey(),
        ];
        $values[$property] = $value;

        $component = Livewire::actingAs($this->user('admin'))->test(CreateInscripciones::class);
        foreach ($values as $name => $fieldValue) {
            $component->set($name, $fieldValue);
        }

        $component->call('save')->assertHasErrors([$property => $rule]);
        $this->assertDatabaseCount('inscripciones', 0);
    }

    public function invalidCreationData(): array
    {
        return [
            'missing date' => ['fecha_inscripcion', null, 'required'],
            'unknown prospect' => ['prospectos_id', 999, 'exists'],
            'unknown course' => ['cursos_id', 999, 'exists'],
            'unknown group' => ['grupo_id', 999, 'exists'],
        ];
    }

    public function test_duplicate_prospect_is_rejected(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $this->enroll($prospecto, $curso, $grupo);

        Livewire::actingAs($this->user('admin'))
            ->test(CreateInscripciones::class)
            ->set('fecha_inscripcion', '2026-08-17')
            ->set('prospectos_id', $prospecto->getKey())
            ->set('cursos_id', $curso->getKey())
            ->set('grupo_id', $grupo->getKey())
            ->call('save')
            ->assertHasErrors(['prospectos_id']);

        $this->assertDatabaseCount('inscripciones', 1);
    }

    public function test_successful_creation_resets_selected_ids_and_closes_modal(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();

        Livewire::actingAs($this->user('admin'))
            ->test(CreateInscripciones::class)
            ->set('open', true)
            ->set('fecha_inscripcion', '2026-08-16')
            ->set('prospectos_id', $prospecto->getKey())
            ->set('cursos_id', $curso->getKey())
            ->set('grupo_id', $grupo->getKey())
            ->call('save')
            ->assertSet('prospectos_id', null)
            ->assertSet('cursos_id', null)
            ->assertSet('grupo_id', null)
            ->assertSet('open', false);

        $this->assertDatabaseCount('inscripciones', 1);
    }

    public function test_update_validates_and_persists_valid_values(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);

        Livewire::actingAs($this->user('admin'))
            ->test(ShowInscripciones::class)
            ->call('edit', $inscripcion->getKey())
            ->set('inscripcion.fecha_inscripcion', '2026-08-20')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inscripciones', [
            'inscripciones_id' => $inscripcion->getKey(),
            'fecha_inscripcion' => '2026-08-20',
        ]);
    }

    public function test_update_uses_whitelist_preserves_creator_and_updates_audit_user(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $creator = $this->user('admin');
        $this->actingAs($creator);
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $editor = $this->user('admin');

        Livewire::actingAs($editor)->test(ShowInscripciones::class)->call('edit', $inscripcion->getKey())
            ->set('inscripcion.created_by', $editor->getKey())->set('inscripcion.fecha_inscripcion', '2026-09-01')
            ->call('update')->assertHasNoErrors();
        $fresh = $inscripcion->fresh();
        $this->assertSame($creator->getKey(), $fresh->created_by);
        $this->assertSame($editor->getKey(), $fresh->updated_by);
        $this->assertSame('2026-09-01', $fresh->fecha_inscripcion->format('Y-m-d'));
    }

    public function test_edit_can_change_or_conserve_responsible_without_duplicates(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $original = ResponsablePago::activeForProspect($prospecto);
        $replacement = ResponsablePago::create(['tipo'=>'persona','nombre_razon_social'=>'Otro','activo'=>true]);
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $inscripcion->update(['responsable_pago_id'=>$original->getKey()]);
        $before = ResponsablePago::count();
        Livewire::actingAs($this->user('admin'))->test(ShowInscripciones::class)->call('edit',$inscripcion->getKey())
            ->call('update')->assertHasNoErrors();
        $this->assertSame($original->getKey(), $inscripcion->fresh()->responsable_pago_id);
        $this->assertSame($before, ResponsablePago::count());

        Livewire::actingAs($this->user('admin'))->test(ShowInscripciones::class)->call('edit',$inscripcion->getKey())
            ->set('responsable_opcion','existente')->set('responsable_pago_id',$replacement->getKey())->call('update')->assertHasNoErrors();
        $this->assertSame($replacement->getKey(), $inscripcion->fresh()->responsable_pago_id);
    }

    public function test_optional_blanks_become_null_and_zero_values_are_preserved(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        Livewire::actingAs($this->user('admin'))->test(ShowInscripciones::class)->call('edit',$inscripcion->getKey())
            ->set('inscripcion.estatus','')->set('inscripcion.fecha_inicio','')->set('inscripcion.fecha_fin','')
            ->set('inscripcion.monto_inscripcion','0')->set('inscripcion.monto_mensualidad','')
            ->set('inscripcion.dia_vencimiento','')->set('inscripcion.numero_mensualidades','')
            ->set('inscripcion.descuento','0')->set('inscripcion.beca',0)->set('inscripcion.observaciones_financieras','')
            ->call('update')->assertHasNoErrors();
        $fresh = $inscripcion->fresh();
        $this->assertNull($fresh->estatus); $this->assertNull($fresh->fecha_inicio); $this->assertNull($fresh->fecha_fin);
        $this->assertSame('0.00',$fresh->monto_inscripcion); $this->assertNull($fresh->monto_mensualidad);
        $this->assertSame('0.00',$fresh->descuento); $this->assertSame('0.00',$fresh->beca);
        $this->assertSame('MXN',$fresh->moneda); $this->assertNull($fresh->observaciones_financieras);
    }

    public function test_update_rejects_missing_date_and_nonexistent_ids(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $component = Livewire::actingAs($this->user('admin'))
            ->test(ShowInscripciones::class)
            ->call('edit', $inscripcion->getKey())
            ->set('inscripcion.fecha_inscripcion', null)
            ->set('inscripcion.prospectos_id', 999)
            ->set('inscripcion.cursos_id', 999)
            ->set('inscripcion.grupo_id', 999)
            ->call('update');

        $component->assertHasErrors([
            'inscripcion.fecha_inscripcion' => 'required',
            'inscripcion.prospectos_id' => 'exists',
            'inscripcion.cursos_id' => 'exists',
            'inscripcion.grupo_id' => 'exists',
        ]);
    }

    public function test_update_rejects_a_prospect_already_assigned_to_another_inscripcion(): void
    {
        [$prospectoA, $curso, $grupo] = $this->catalogs();
        $prospectoB = new Prospecto(['prospectos_nombres' => 'Alumno B', 'prospectos_telefono1' => '5550000001']);
        $prospectoB->save();
        $inscripcionA = $this->enroll($prospectoA, $curso, $grupo);
        $inscripcionB = $this->enroll($prospectoB, $curso, $grupo);

        Livewire::actingAs($this->user('admin'))
            ->test(ShowInscripciones::class)
            ->call('edit', $inscripcionB->getKey())
            ->set('inscripcion.prospectos_id', $prospectoA->getKey())
            ->call('update')
            ->assertHasErrors(['inscripcion.prospectos_id']);

        $this->assertDatabaseHas('inscripciones', [
            'inscripciones_id' => $inscripcionA->getKey(),
            'prospectos_id' => $prospectoA->getKey(),
        ]);
        $this->assertDatabaseHas('inscripciones', [
            'inscripciones_id' => $inscripcionB->getKey(),
            'prospectos_id' => $prospectoB->getKey(),
        ]);
        $this->assertDatabaseCount('inscripciones', 2);
    }

    public function test_pagination_and_safe_ordering_continue_to_work(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $this->enroll($prospecto, $curso, $grupo);

        $component = Livewire::actingAs($this->user('admin'))
            ->test(ShowInscripciones::class)
            ->set('cant', 1)
            ->call('loadPosts')
            ->call('order', 'fecha_inscripcion')
            ->assertSet('sort', 'fecha_inscripcion')
            ->assertViewHas('inscripciones', fn ($items) => $items->perPage() === 1);

        $component->call('order', 'untrusted_column')
            ->assertSet('sort', 'inscripciones_id')
            ->assertSet('direction', 'asc');
    }

    public function test_unauthorized_user_cannot_mount_update_or_delete_components(): void
    {
        $user = $this->user('venta');

        Livewire::actingAs($user)->test(ShowInscripciones::class)->assertForbidden();
        Livewire::actingAs($user)->test(CreateInscripciones::class)->assertForbidden();
    }

    /** @dataProvider unauthorizedRolesAndActions */
    public function test_non_admin_roles_cannot_execute_update_or_delete(string $role, string $action): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $this->actingAs($this->user($role));
        $component = app(ShowInscripciones::class);
        $component->inscripcion = $inscripcion;

        $this->expectException(AuthorizationException::class);
        $action === 'update' ? $component->update() : $component->delete($inscripcion);
    }

    public function unauthorizedRolesAndActions(): array
    {
        return [
            'venta update' => ['venta', 'update'],
            'venta delete' => ['venta', 'delete'],
            'profe update' => ['profe', 'update'],
            'profe delete' => ['profe', 'delete'],
            'alum update' => ['alum', 'update'],
            'alum delete' => ['alum', 'delete'],
        ];
    }

    public function test_create_prospect_enrollment_flow_remains_compatible(): void
    {
        [, $curso, $grupo] = $this->catalogs();
        $component = app(CreateProspect::class);
        $component->prospectos_nombres = 'Nuevo alumno';
        $component->prospectos_apellidos = 'Apellido';
        $component->prospectos_telefono1 = '5551111111';
        $component->prospectos_correo = 'alumno@example.com';
        $component->prospectos_fecha = '2026-08-16';
        $component->seguimientos_id = 8;
        $component->cursos_id = $curso->getKey();
        $component->grupoid = $grupo->getKey();
        $component->save();

        $prospecto = Prospecto::where('prospectos_correo','alumno@example.com')->firstOrFail();
        $responsable = ResponsablePago::where('prospectos_id',$prospecto->getKey())->firstOrFail();
        $this->assertDatabaseHas('inscripciones', [
            'cursos_id' => $curso->getKey(),
            'grupo_id' => $grupo->getKey(),
            'fecha_inscripcion' => '2026-08-16', 'fecha_inicio' => '2026-08-16', 'fecha_fin' => null,
            'estatus' => 'activa', 'moneda' => 'MXN', 'descuento' => 0, 'beca' => 0,
            'monto_inscripcion' => null, 'monto_mensualidad' => null, 'dia_vencimiento' => null,
            'numero_mensualidades' => null, 'observaciones_financieras' => null,
            'responsable_pago_id' => $responsable->getKey(),
        ]);
        $this->assertSame('persona',$responsable->tipo);
        $this->assertSame('Nuevo alumno Apellido',$responsable->nombre_razon_social);
        $this->assertSame('5551111111',$responsable->telefono);
        $this->assertSame('alumno@example.com',$responsable->correo);
    }

    public function test_financial_configuration_scopes_distinguish_null_zero_and_positive_monthly_amounts(): void
    {
        [$pendingProspect, $curso, $grupo] = $this->catalogs();
        $pending = $this->enroll($pendingProspect, $curso, $grupo);
        $configuredProspect = Prospecto::create(['prospectos_nombres' => 'Configurado', 'prospectos_telefono1' => '1']);
        $responsable = ResponsablePago::activeForProspect($configuredProspect);
        $configured = Inscripcion::create([
            'fecha_inscripcion' => '2026-08-16', 'prospectos_id' => $configuredProspect->getKey(),
            'cursos_id' => $curso->getKey(), 'grupo_id' => $grupo->getKey(), 'estatus' => 'activa',
            'fecha_inicio' => '2026-08-16', 'moneda' => 'MXN', 'monto_inscripcion' => '0.00',
            'monto_mensualidad' => '0.00', 'descuento' => '0.00', 'beca' => '0.00',
            'responsable_pago_id' => $responsable->getKey(),
        ]);

        $this->assertTrue($configured->financieramente_configurada);
        $this->assertSame('configurada', $configured->estado_configuracion_financiera);
        $this->assertFalse($pending->financieramente_configurada);
        $this->assertEquals([$configured->getKey()], Inscripcion::financieramenteConfiguradas()->pluck('inscripciones_id')->all());
        $this->assertEquals([$pending->getKey()], Inscripcion::financieramentePendientes()->pluck('inscripciones_id')->all());

        $configured->update(['monto_mensualidad' => '100.00']);
        $this->assertFalse($configured->fresh()->financieramente_configurada);
        $configured->update(['dia_vencimiento' => 15, 'numero_mensualidades' => 120]);
        $this->assertTrue($configured->fresh()->financieramente_configurada);
    }

    public function test_financial_filter_and_counters_are_rendered_server_side(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $pending = $this->enroll($prospecto, $curso, $grupo);

        Livewire::actingAs($this->user('admin'))->test(ShowInscripciones::class)
            ->call('loadPosts')->set('filtroFinanciero', 'pendientes')
            ->assertSee((string) $pending->getKey())->assertSee('Pendientes: 1')->assertSee('Configuradas: 0')
            ->set('filtroFinanciero', 'configuradas')->assertDontSee('Alumno');
    }

    /** @dataProvider invalidFinancialConfigurations */
    public function test_canonical_financial_scope_rejects_every_invalid_configuration(array $invalid): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $responsable = ResponsablePago::activeForProspect($prospecto);
        $values = array_merge([
            'fecha_inscripcion' => '2026-08-16', 'prospectos_id' => $prospecto->getKey(),
            'cursos_id' => $curso->getKey(), 'grupo_id' => $grupo->getKey(), 'estatus' => 'activa',
            'fecha_inicio' => '2026-08-16', 'fecha_fin' => null, 'moneda' => 'MXN',
            'monto_inscripcion' => '100.00', 'monto_mensualidad' => '100.00',
            'dia_vencimiento' => 15, 'numero_mensualidades' => 12, 'descuento' => '0.00',
            'beca' => '0.00', 'responsable_pago_id' => $responsable->getKey(),
        ], $invalid);
        $id = DB::table('inscripciones')->insertGetId($values);

        $this->assertFalse(Inscripcion::findOrFail($id)->financieramente_configurada);
        $this->assertTrue(Inscripcion::financieramentePendientes()->whereKey($id)->exists());
    }

    public function invalidFinancialConfigurations(): array
    {
        return [
            'invalid status' => [['estatus' => 'otra']], 'end before start' => [['fecha_fin' => '2026-08-15']],
            'negative enrollment' => [['monto_inscripcion' => -1]], 'negative monthly' => [['monto_mensualidad' => -1]],
            'negative discount' => [['descuento' => -1]], 'negative scholarship' => [['beca' => -1]],
            'discount over 100' => [['descuento' => 101]], 'scholarship over 100' => [['beca' => 101]],
            'sum over 100' => [['descuento' => 60, 'beca' => 41]], 'missing responsible' => [['responsable_pago_id' => null]],
            'unknown responsible' => [['responsable_pago_id' => 99999]],
            'positive monthly missing day' => [['dia_vencimiento' => null]],
            'positive monthly missing count' => [['numero_mensualidades' => null]],
            'day too low' => [['dia_vencimiento' => 0]], 'day too high' => [['dia_vencimiento' => 32]],
            'count too low' => [['numero_mensualidades' => 0]], 'count too high' => [['numero_mensualidades' => 121]],
        ];
    }

    public function test_canonical_financial_scope_accepts_boundary_cases_and_rejects_inactive_responsible(): void
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $responsable = ResponsablePago::activeForProspect($prospecto);
        $configured = Inscripcion::create([
            'fecha_inscripcion' => '2026-08-16', 'prospectos_id' => $prospecto->getKey(), 'cursos_id' => $curso->getKey(),
            'grupo_id' => $grupo->getKey(), 'estatus' => 'finalizada', 'fecha_inicio' => '2026-08-16',
            'fecha_fin' => null, 'moneda' => 'MXN', 'monto_inscripcion' => '9999999999.99',
            'monto_mensualidad' => '0.00', 'dia_vencimiento' => null, 'numero_mensualidades' => null,
            'descuento' => '40.00', 'beca' => '60.00', 'responsable_pago_id' => $responsable->getKey(),
        ]);
        $this->assertTrue($configured->financieramente_configurada);
        $configured->update(['monto_mensualidad' => '1.00', 'dia_vencimiento' => 31, 'numero_mensualidades' => 120]);
        $this->assertTrue($configured->fresh()->financieramente_configurada);
        $responsable->update(['activo' => false]);
        $this->assertFalse($configured->fresh()->financieramente_configurada);
    }

    public function test_listing_loads_financial_state_in_a_constant_number_of_queries(): void
    {
        [$pendingProspect, $curso, $grupo] = $this->catalogs();
        $pending = $this->enroll($pendingProspect, $curso, $grupo);
        $configuredProspect = Prospecto::create(['prospectos_nombres' => 'Configurado', 'prospectos_telefono1' => '2']);
        $responsable = ResponsablePago::activeForProspect($configuredProspect);
        $configured = Inscripcion::create([
            'fecha_inscripcion' => '2026-08-16', 'prospectos_id' => $configuredProspect->getKey(),
            'cursos_id' => $curso->getKey(), 'grupo_id' => $grupo->getKey(), 'estatus' => 'activa',
            'fecha_inicio' => '2026-08-16', 'moneda' => 'MXN', 'monto_inscripcion' => '0.00',
            'monto_mensualidad' => '0.00', 'descuento' => '0.00', 'beca' => '0.00',
            'responsable_pago_id' => $responsable->getKey(),
        ]);
        $admin = $this->user('admin');

        DB::enableQueryLog();
        Livewire::actingAs($admin)->test(ShowInscripciones::class)->set('cant', 50)->call('loadPosts');
        $smallQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        for ($i = 2; $i < 25; $i++) {
            $student = Prospecto::create(['prospectos_nombres' => 'Pendiente '.$i, 'prospectos_telefono1' => (string) $i]);
            $this->enroll($student, $curso, $grupo);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $listing = Livewire::actingAs($admin)->test(ShowInscripciones::class)->set('cant', 50)->call('loadPosts');
        $largeQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual($smallQueryCount + 3, $largeQueryCount,
            "Las consultas crecieron de {$smallQueryCount} a {$largeQueryCount}; posible regresión N+1.");
        $pendingRow = 'wire:click="edit('.$pending->getKey().')"';
        $configuredRow = 'wire:click="edit('.$configured->getKey().')"';

        $listing->assertSee('Configurada')->assertSee('Pendiente')
            ->assertSee('Total: 25')->assertSee('Pendientes: 24')->assertSee('Configuradas: 1')
            ->set('filtroFinanciero', 'pendientes')->assertSeeHtml($pendingRow)->assertDontSeeHtml($configuredRow)
            ->set('filtroFinanciero', 'configuradas')->assertSeeHtml($configuredRow)->assertDontSeeHtml($pendingRow);
    }

}
