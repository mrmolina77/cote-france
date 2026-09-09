<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowMetodosPago;
use App\Models\MetodoPago;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MetodoPagoCrudConsistencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Schema::create('roles', function (Blueprint $table) { $table->id('roles_id'); $table->string('roles_codigo'); $table->string('roles_nombre'); $table->timestamps(); });
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('email')->unique(); $table->timestamp('email_verified_at')->nullable(); $table->string('password'); $table->text('two_factor_secret')->nullable(); $table->text('two_factor_recovery_codes')->nullable(); $table->rememberToken(); $table->foreignId('current_team_id')->nullable(); $table->string('profile_photo_path', 2048)->nullable(); $table->unsignedBigInteger('roles_id')->nullable(); $table->timestamps(); });
        (require database_path('migrations/2026_09_08_000001_create_metodos_pago_table.php'))->up();
        $role = Role::create(['roles_codigo' => 'admin', 'roles_nombre' => 'Admin']);
        $this->actingAs(User::factory()->create(['roles_id' => $role->getKey()]));
    }

    /** @dataProvider dependencias */
    public function test_creacion_aplica_cada_dependencia_automatica(string $causa, array $efectos): void
    {
        Livewire::test(ShowMetodosPago::class)->set('clave', 'PERSONALIZADO')->set('nombre', 'Personalizado')->set($causa, true)->call('store')->assertHasNoErrors();
        $metodo = MetodoPago::where('clave', 'PERSONALIZADO')->firstOrFail();
        $this->assertTrue($metodo->{$causa});
        foreach ($efectos as $efecto) $this->assertTrue($metodo->{$efecto}, $efecto);
    }

    /** @dataProvider dependencias */
    public function test_edicion_aplica_cada_dependencia_automatica(string $causa, array $efectos): void
    {
        $metodo = MetodoPago::create(['clave' => 'PERSONALIZADO', 'nombre' => 'Personalizado']);
        $componente = Livewire::test(ShowMetodosPago::class)->call('edit', $metodo->getKey());
        foreach ($efectos as $efecto) $componente->set($efecto, false);
        $componente->set($causa, true)->call('update')->assertHasNoErrors();
        $metodo->refresh();
        foreach ($efectos as $efecto) $this->assertTrue($metodo->{$efecto}, $efecto);
    }

    public static function dependencias(): array
    {
        return [
            'SPEI exige banco y referencia' => ['requiere_rastreo_spei', ['requiere_banco', 'requiere_referencia']],
            'cheque exige banco' => ['requiere_numero_cheque', ['requiere_banco']],
            'últimos cuatro exige terminal' => ['requiere_ultimos_4_digitos', ['requiere_terminal']],
        ];
    }

    public function test_creacion_rechaza_contradiccion_sat_sin_insertar(): void
    {
        Livewire::test(ShowMetodosPago::class)->set('clave', 'CONTRADICTORIO')->set('nombre', 'Contradictorio')->set('clave_forma_pago_sat', '03')->set('requiere_forma_pago_sat', true)->call('store')->assertHasErrors(['requiere_forma_pago_sat' => 'prohibited']);
        $this->assertDatabaseCount('metodos_pago', 0);
    }

    public function test_edicion_rechaza_contradiccion_sat_sin_cambios_parciales(): void
    {
        $metodo = MetodoPago::create(['clave' => 'INMUTABLE', 'nombre' => 'Original', 'descripcion' => 'Conservar', 'orden' => 7, 'activo' => true, 'requiere_banco' => true, 'requiere_comprobante' => true]);
        $antes = $metodo->only($metodo->getFillable());

        Livewire::test(ShowMetodosPago::class)->call('edit', $metodo->getKey())->set('clave', 'MANIPULADA')->set('nombre', 'Alterado')->set('descripcion', 'Alterada')->set('orden', 99)->set('activo', false)->set('requiere_banco', false)->set('requiere_terminal', true)->set('clave_forma_pago_sat', '03')->set('requiere_forma_pago_sat', true)->call('update')->assertHasErrors(['requiere_forma_pago_sat' => 'prohibited']);

        $metodo->refresh();
        $this->assertSame($antes, $metodo->only(array_keys($antes)));
    }

    public function test_id_manipulado_no_edita_otro_metodo_y_clave_es_inmutable(): void
    {
        $uno = MetodoPago::create(['clave' => 'UNO', 'nombre' => 'Uno']);
        $dos = MetodoPago::create(['clave' => 'DOS', 'nombre' => 'Dos']);
        $antesDos = $dos->getAttributes();
        Livewire::test(ShowMetodosPago::class)->call('edit', $uno->getKey())->set('editingId', $dos->getKey())->set('clave', 'HACK')->set('nombre', 'Hack')->call('update')->assertStatus(404);
        $this->assertSame($antesDos, $dos->fresh()->getAttributes());
        $this->assertSame('UNO', $uno->fresh()->clave);
    }
}
