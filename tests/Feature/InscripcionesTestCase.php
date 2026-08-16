<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Prospecto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class InscripcionesTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        $this->createSchema();
    }

    protected function user(string $code): User
    {
        $role = Role::create(['roles_codigo' => $code, 'roles_nombre' => ucfirst($code)]);

        return User::factory()->create(['roles_id' => $role->getKey()]);
    }

    protected function catalogs(): array
    {
        $prospecto = Prospecto::create(['prospectos_nombres' => 'Alumno', 'prospectos_telefono1' => '5550000000']);
        $curso = Curso::create(['cursos_descripcion' => 'Francés', 'cursos_fecha_creacion' => '2026-08-16']);
        $grupo = Grupo::create(['grupo_nombre' => 'A1', 'modalidad_id' => 1]);

        return [$prospecto, $curso, $grupo];
    }

    protected function enroll(Prospecto $prospecto, Curso $curso, Grupo $grupo): Inscripcion
    {
        return Inscripcion::create([
            'fecha_inscripcion' => '2026-08-16',
            'prospectos_id' => $prospecto->getKey(),
            'cursos_id' => $curso->getKey(),
            'grupo_id' => $grupo->getKey(),
        ]);
    }

    protected function createSchema(): void
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
            $table->unsignedBigInteger('roles_id');
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
        Schema::create('responsables_pago', function (Blueprint $table) {
            $table->id('responsable_pago_id');
            $table->string('tipo', 20);
            $table->unsignedBigInteger('prospectos_id')->nullable();
            $table->string('nombre_razon_social');
            $table->string('telefono', 80)->nullable();
            $table->string('correo')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id('inscripciones_id');
            $table->date('fecha_inscripcion');
            $table->unsignedBigInteger('prospectos_id');
            $table->unsignedBigInteger('cursos_id');
            $table->unsignedBigInteger('grupo_id');
            $table->string('estatus', 20)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->char('moneda', 3)->default('MXN');
            $table->decimal('monto_inscripcion', 12, 2)->nullable();
            $table->decimal('monto_mensualidad', 12, 2)->nullable();
            $table->unsignedTinyInteger('dia_vencimiento')->nullable();
            $table->unsignedSmallInteger('numero_mensualidades')->nullable();
            $table->decimal('descuento', 5, 2)->nullable();
            $table->decimal('beca', 5, 2)->nullable();
            $table->text('observaciones_financieras')->nullable();
            $table->unsignedBigInteger('responsable_pago_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
