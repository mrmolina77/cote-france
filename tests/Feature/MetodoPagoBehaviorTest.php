<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowMetodosPago;
use App\Models\MetodoPago;
use App\Models\Role;
use App\Models\User;
use App\Services\Facturacion\MetodoPagoBehaviorService;
use Database\Seeders\MetodoPagoSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class MetodoPagoBehaviorTest extends TestCase
{
    private MetodoPagoBehaviorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->migration()->up();
        $this->seed(MetodoPagoSeeder::class);
        $this->service = new MetodoPagoBehaviorService();
    }

    public function test_catalogo_cubre_cada_indicador_una_vez(): void
    {
        $catalogo = $this->service->catalogoCampos();
        $esperados = array_values(array_filter(array_keys((new MetodoPago())->getCasts()), fn ($campo) => str_starts_with($campo, 'requiere_')));

        $this->assertSame($esperados, array_keys($catalogo));
        $this->assertCount(count($catalogo), array_unique(array_column($catalogo, 'campo')));
        $this->assertSame('numero_autorizacion', $catalogo['requiere_autorizacion']['campo']);
        $this->assertSame('anticipo_relacionado_id', $catalogo['requiere_anticipo_relacionado']['campo']);
    }

    public function test_campos_se_derivan_de_indicadores_incluso_en_metodo_personalizado(): void
    {
        $custom = MetodoPago::create(['clave' => 'PERSONALIZADO', 'nombre' => 'Personalizado', 'requiere_banco' => true, 'requiere_ultimos_4_digitos' => true]);

        $this->assertSame(['banco', 'ultimos_4_digitos'], array_keys($this->service->camposAplicables($custom)));
        $this->assertSame(['banco', 'ultimos_4_digitos'], $this->service->camposObligatorios($custom));
        $this->assertContains('required', $this->service->reglasValidacion($custom)['banco']);
    }

    public function test_catalogo_inicial_produce_campos_observables_esperados(): void
    {
        $casos = [
            MetodoPago::EFECTIVO => [],
            MetodoPago::CHEQUE_NOMINATIVO => ['banco', 'numero_cheque', 'comprobante'],
            MetodoPago::TRANSFERENCIA_SPEI => ['banco', 'referencia', 'rastreo_spei', 'comprobante'],
            MetodoPago::TARJETA_CREDITO => ['numero_autorizacion', 'terminal', 'ultimos_4_digitos'],
            MetodoPago::DEPOSITO_BANCARIO => ['forma_pago_sat', 'banco', 'referencia', 'comprobante'],
        ];
        foreach ($casos as $clave => $campos) {
            $metodo = MetodoPago::where('clave', $clave)->firstOrFail();
            $this->assertSame($campos, array_keys($this->service->camposAplicables($metodo)), $clave);
        }
    }

    public function test_reglas_condicionales_validan_digitos_sat_e_identificadores(): void
    {
        $metodo = MetodoPago::create(['clave' => 'REGLAS', 'nombre' => 'Reglas', 'requiere_ultimos_4_digitos' => true, 'requiere_forma_pago_sat' => true, 'requiere_anticipo_relacionado' => true]);
        $rules = $this->service->reglasValidacion($metodo);
        $this->assertFalse(Validator::make(['ultimos_4_digitos' => '1234', 'forma_pago_sat' => '03', 'anticipo_relacionado_id' => 1], $rules)->fails());
        foreach (['ABC4', '123', '12345'] as $invalido) {
            $this->assertTrue(Validator::make(['ultimos_4_digitos' => $invalido], ['ultimos_4_digitos' => $rules['ultimos_4_digitos']])->fails());
        }
        $this->assertTrue(Validator::make(['forma_pago_sat' => '3'], ['forma_pago_sat' => $rules['forma_pago_sat']])->fails());
        $this->assertTrue(Validator::make(['anticipo_relacionado_id' => 0], ['anticipo_relacionado_id' => $rules['anticipo_relacionado_id']])->fails());
    }

    public function test_forma_sat_fija_no_es_manipulable_y_la_seleccionable_es_obligatoria(): void
    {
        $fijo = MetodoPago::where('clave', MetodoPago::INTERMEDIARIO_PAGOS)->firstOrFail();
        $deposito = MetodoPago::where('clave', MetodoPago::DEPOSITO_BANCARIO)->firstOrFail();
        $ninguna = MetodoPago::create(['clave' => 'SIN_SAT', 'nombre' => 'Sin SAT']);

        $this->assertSame('31', $this->service->resolverFormaPagoSat($fijo, '99'));
        $this->assertSame('03', $this->service->resolverFormaPagoSat($deposito, '03'));
        $this->assertNull($this->service->resolverFormaPagoSat($ninguna, '99'));
        $this->expectException(ValidationException::class);
        $this->service->resolverFormaPagoSat($deposito, null);
    }

    public function test_solo_metodos_activos_pueden_seleccionarse_del_lado_servidor(): void
    {
        $activo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $this->assertTrue($this->service->seleccionarActivo($activo->getKey())->is($activo));
        $activo->update(['activo' => false]);
        $this->assertFalse($this->service->metodosDisponibles()->contains($activo));
        foreach ([$activo->getKey(), 999999, '1 OR 1=1'] as $id) {
            try { $this->service->seleccionarActivo($id); $this->fail('Debió rechazar el método.'); }
            catch (ValidationException $exception) { $this->assertArrayHasKey('metodo_pago_id', $exception->errors()); }
        }
    }

    public function test_normalizacion_descarta_ocultos_limpia_cambios_y_no_muta_el_modelo(): void
    {
        $spei = MetodoPago::where('clave', MetodoPago::TRANSFERENCIA_SPEI)->firstOrFail();
        $efectivo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $antes = $spei->getAttributes();
        $datos = ['banco' => '  Banco  ', 'referencia' => '', 'rastreo_spei' => 'R1', 'terminal' => 'hack'];

        $this->assertSame(['banco' => 'Banco', 'referencia' => null, 'rastreo_spei' => 'R1'], $this->service->normalizarDatos($spei, $datos));
        $this->assertSame([], $this->service->normalizarDatos($efectivo, $datos));
        $this->assertSame($antes, $spei->getAttributes());
    }

    public function test_crud_normaliza_dependencias_y_rechaza_configuracion_sat_contradictoria_atomicamente(): void
    {
        Schema::create('roles', function (Blueprint $table) { $table->id('roles_id'); $table->string('roles_codigo'); $table->string('roles_nombre'); $table->timestamps(); });
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('email')->unique(); $table->timestamp('email_verified_at')->nullable(); $table->string('password'); $table->text('two_factor_secret')->nullable(); $table->text('two_factor_recovery_codes')->nullable(); $table->rememberToken(); $table->foreignId('current_team_id')->nullable(); $table->string('profile_photo_path', 2048)->nullable(); $table->unsignedBigInteger('roles_id')->nullable(); $table->timestamps(); });
        $role = Role::create(['roles_codigo' => 'admin', 'roles_nombre' => 'Admin']);
        $user = User::factory()->create(['roles_id' => $role->getKey()]);
        $this->actingAs($user);

        Livewire::test(ShowMetodosPago::class)->set('clave', 'DEPENDENCIAS')->set('nombre', 'Dependencias')->set('requiere_rastreo_spei', true)->set('requiere_numero_cheque', true)->set('requiere_ultimos_4_digitos', true)->call('store')->assertHasNoErrors();
        $guardado = MetodoPago::where('clave', 'DEPENDENCIAS')->firstOrFail();
        $this->assertTrue($guardado->requiere_banco && $guardado->requiere_referencia && $guardado->requiere_terminal);

        Livewire::test(ShowMetodosPago::class)->call('edit', $guardado->getKey())->set('nombre', 'No guardar')->set('clave_forma_pago_sat', '03')->set('requiere_forma_pago_sat', true)->call('update')->assertHasErrors('requiere_forma_pago_sat');
        $this->assertSame('Dependencias', $guardado->fresh()->nombre);
    }

    public function test_migracion_de_alineacion_es_selectiva_y_reversible(): void
    {
        $intermediario = MetodoPago::where('clave', MetodoPago::INTERMEDIARIO_PAGOS)->firstOrFail();
        $intermediario->update(['clave_forma_pago_sat' => null, 'requiere_forma_pago_sat' => true]);
        $personalizado = MetodoPago::create(['clave' => 'INTERMEDIARIO_CONFIGURADO', 'nombre' => 'Configurado', 'clave_forma_pago_sat' => '99', 'requiere_forma_pago_sat' => false]);
        $migration = require database_path('migrations/2026_09_09_000001_align_intermediario_pagos_sat_key.php');

        $migration->up();
        $this->assertSame('31', $intermediario->fresh()->clave_forma_pago_sat);
        $this->assertSame('99', $personalizado->fresh()->clave_forma_pago_sat);
        $migration->down();
        $this->assertNull($intermediario->fresh()->clave_forma_pago_sat);
        $this->assertTrue($intermediario->requiere_forma_pago_sat);

        $intermediario->update(['nombre' => 'Configuración administrativa', 'clave_forma_pago_sat' => '31', 'requiere_forma_pago_sat' => false]);
        $migration->down();
        $this->assertSame('31', $intermediario->fresh()->clave_forma_pago_sat);
    }

    private function migration()
    {
        return require database_path('migrations/2026_09_08_000001_create_metodos_pago_table.php');
    }
}
