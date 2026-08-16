<?php

namespace Tests\Feature;

use App\Http\Livewire\CreateInscripciones;
use App\Http\Livewire\CreateProspect;
use App\Http\Livewire\ShowInscripciones;
use App\Models\Curso;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Prospecto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class InscripcionesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        $this->createSchema();
    }

    public function test_admin_can_access_inscripciones_and_other_roles_receive_403(): void
    {
        $this->actingAs($this->user('admin'))->get('/inscripciones')->assertOk();
        $this->actingAs($this->user('venta'))->get('/inscripciones')->assertForbidden();
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

    public function test_create_prospect_enrollment_flow_remains_compatible(): void
    {
        [, $curso, $grupo] = $this->catalogs();
        $component = app(CreateProspect::class);
        $component->prospectos_nombres = 'Nuevo alumno';
        $component->prospectos_telefono1 = '5551111111';
        $component->prospectos_fecha = '2026-08-16';
        $component->seguimientos_id = 8;
        $component->cursos_id = $curso->getKey();
        $component->grupoid = $grupo->getKey();
        $component->save();

        $this->assertDatabaseHas('inscripciones', [
            'cursos_id' => $curso->getKey(),
            'grupo_id' => $grupo->getKey(),
        ]);
    }

    private function user(string $code): User
    {
        $role = new Role();
        $role->roles_codigo = $code;
        $role->roles_nombre = ucfirst($code);
        $role->save();

        return User::factory()->create(['roles_id' => $role->getKey()]);
    }

    private function catalogs(): array
    {
        $prospecto = new Prospecto(['prospectos_nombres' => 'Alumno', 'prospectos_telefono1' => '5550000000']);
        $prospecto->save();
        $curso = new Curso();
        $curso->cursos_descripcion = 'Francés';
        $curso->cursos_fecha_creacion = '2026-08-16';
        $curso->save();
        $grupo = new Grupo(['grupo_nombre' => 'A1', 'modalidad_id' => 1]);
        $grupo->save();

        return [$prospecto, $curso, $grupo];
    }

    private function enroll(Prospecto $prospecto, Curso $curso, Grupo $grupo): Inscripcion
    {
        return Inscripcion::create([
            'fecha_inscripcion' => '2026-08-16',
            'prospectos_id' => $prospecto->getKey(),
            'cursos_id' => $curso->getKey(),
            'grupo_id' => $grupo->getKey(),
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id('roles_id');
            $table->string('roles_codigo');
            $table->string('roles_nombre');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->unsignedBigInteger('roles_id')->default(1);
            $table->timestamps();
        });
        Schema::create('prospectos', function (Blueprint $table) {
            $table->id('prospectos_id');
            $table->string('prospectos_nombres');
            $table->string('prospectos_apellidos')->nullable();
            $table->string('prospectos_telefono1');
            $table->string('prospectos_telefono2')->nullable();
            $table->string('prospectos_correo')->nullable();
            $table->unsignedBigInteger('origenes_id')->nullable();
            $table->unsignedBigInteger('seguimientos_id')->nullable();
            $table->unsignedBigInteger('estatus_id')->nullable();
            $table->unsignedBigInteger('modalidad_id')->nullable();
            $table->unsignedBigInteger('cursos_id')->nullable();
            $table->text('prospectos_comentarios')->nullable();
            $table->date('prospectos_fecha')->nullable();
            $table->unsignedBigInteger('grupo_id')->nullable();
            $table->unsignedBigInteger('horarios_id')->nullable();
            $table->timestamps();
        });
        Schema::create('cursos', function (Blueprint $table) {
            $table->id('cursos_id');
            $table->string('cursos_descripcion');
            $table->date('cursos_fecha_creacion');
            $table->timestamps();
        });
        Schema::create('grupos', function (Blueprint $table) {
            $table->id('grupo_id');
            $table->string('grupo_nombre');
            $table->unsignedBigInteger('modalidad_id');
            $table->timestamps();
        });
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id('inscripciones_id');
            $table->date('fecha_inscripcion');
            $table->unsignedBigInteger('prospectos_id');
            $table->unsignedBigInteger('cursos_id');
            $table->unsignedBigInteger('grupo_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
