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
            MetodoPago::MONEDERO_ELECTRONICO => ['referencia', 'proveedor'],
            MetodoPago::DINERO_ELECTRONICO => ['referencia', 'proveedor'],
            MetodoPago::TARJETA_DEBITO => ['numero_autorizacion', 'terminal', 'ultimos_4_digitos'],
            MetodoPago::TARJETA_SERVICIOS => ['numero_autorizacion'],
            MetodoPago::APLICACION_ANTICIPO => ['anticipo_relacionado_id'],
            MetodoPago::INTERMEDIARIO_PAGOS => ['referencia', 'proveedor'],
            MetodoPago::POR_DEFINIR => [],
            MetodoPago::DEPOSITO_BANCARIO => ['forma_pago_sat', 'banco', 'referencia', 'comprobante'],
        ];
        foreach ($casos as $clave => $campos) {
            $metodo = MetodoPago::where('clave', $clave)->firstOrFail();
            $this->assertSame($campos, array_keys($this->service->camposAplicables($metodo)), $clave);
        }
    }

    public function test_flujo_integral_normaliza_valida_descarta_campos_y_fija_sat(): void
    {
        $intermediario = MetodoPago::where('clave', MetodoPago::INTERMEDIARIO_PAGOS)->firstOrFail();
        $antes = $intermediario->getAttributes();

        $resultado = $this->service->validarYNormalizarParaNuevoPago($intermediario->getKey(), [
            'proveedor' => '  Proveedor  ', 'referencia' => ' REF ', 'terminal' => 'oculto', 'forma_pago_sat' => '99',
        ]);

        $this->assertTrue($resultado['metodo']->is($intermediario));
        $this->assertSame(['referencia' => 'REF', 'proveedor' => 'Proveedor', 'forma_pago_sat' => '31'], $resultado['datos']);
        $this->assertSame($antes, $intermediario->fresh()->getAttributes());
    }

    public function test_flujo_integral_normaliza_antes_de_validar_campos_obligatorios(): void
    {
        $spei = MetodoPago::where('clave', MetodoPago::TRANSFERENCIA_SPEI)->firstOrFail();
        $this->expectException(ValidationException::class);
        $this->service->validarYNormalizarParaNuevoPago($spei->getKey(), [
            'banco' => '   ', 'referencia' => 'R', 'rastreo_spei' => 'S', 'comprobante' => 'archivo',
        ]);
    }

    public function test_flujo_integral_rechaza_ids_invalidos_inexistentes_e_inactivos(): void
    {
        $metodo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $metodo->update(['activo' => false]);

        foreach ([null, '', 0, -1, 1.5, 'texto', '1 OR 1=1', 999999, $metodo->getKey()] as $id) {
            try {
                $this->service->validarYNormalizarParaNuevoPago($id, ['banco' => 'no debe devolverse']);
                $this->fail('Debió rechazar el método de pago.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('metodo_pago_id', $exception->errors());
            }
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
        $intermediario->refresh();
        $this->assertNull($intermediario->clave_forma_pago_sat);
        $this->assertTrue($intermediario->requiere_forma_pago_sat);

        $intermediario->update(['nombre' => 'Configuración administrativa', 'clave_forma_pago_sat' => '31', 'requiere_forma_pago_sat' => false]);
        $migration->down();
        $this->assertSame('31', $intermediario->fresh()->clave_forma_pago_sat);
    }

    public function test_migracion_no_toca_el_intermediario_objetivo_si_fue_personalizado(): void
    {
        $migration = require database_path('migrations/2026_09_09_000001_align_intermediario_pagos_sat_key.php');
        $intermediario = MetodoPago::where('clave', MetodoPago::INTERMEDIARIO_PAGOS)->firstOrFail();
        $base = ['clave_forma_pago_sat' => null, 'requiere_forma_pago_sat' => true];

        foreach ([
            ['nombre' => 'Nombre administrativo'],
            ['orden' => 101],
            ['activo' => false],
            ['requiere_banco' => true],
            ['clave_forma_pago_sat' => '99', 'requiere_forma_pago_sat' => true],
            ['requiere_forma_pago_sat' => false],
        ] as $personalizacion) {
            $intermediario->update(array_merge($base, $personalizacion));
            $migration->up();
            $intermediario->refresh();
            $this->assertSame($personalizacion['clave_forma_pago_sat'] ?? null, $intermediario->clave_forma_pago_sat);
            $this->assertSame($personalizacion['requiere_forma_pago_sat'] ?? true, $intermediario->requiere_forma_pago_sat);
            $intermediario->update([
                'nombre' => 'Intermediario de pagos', 'orden' => 100, 'activo' => true, 'requiere_banco' => false,
            ]);
        }
    }

    /** @dataProvider metodosParaConfiguracion */
    public function test_configuracion_expone_contrato_completo_y_coherente(string $clave): void
    {
        $metodo = $clave === 'PERSONALIZADO'
            ? MetodoPago::create(['clave' => $clave, 'nombre' => 'Método personalizado', 'requiere_banco' => true, 'requiere_comprobante' => true])
            : MetodoPago::where('clave', $clave)->firstOrFail();
        $configuracion = $this->service->configuracion($metodo);

        $this->assertSame(['metodo_pago_id', 'activo', 'forma_pago_sat_fija', 'campos', 'reglas', 'etiquetas'], array_keys($configuracion));
        $this->assertSame($metodo->getKey(), $configuracion['metodo_pago_id']);
        $this->assertSame($metodo->activo, $configuracion['activo']);
        $this->assertSame($metodo->tieneFormaPagoSatFija() ? $metodo->clave_forma_pago_sat : null, $configuracion['forma_pago_sat_fija']);
        $this->assertSame(array_keys($configuracion['campos']), array_keys($configuracion['reglas']));
        $this->assertSame(array_keys($configuracion['campos']), array_keys($configuracion['etiquetas']));
        $this->assertCount(count($configuracion['campos']), array_unique(array_keys($configuracion['campos'])));
        $this->assertCount(count($configuracion['campos']), array_unique(array_column($configuracion['campos'], 'indicador')));
        foreach ($configuracion['campos'] as $campo => $metadata) {
            $this->assertTrue($metodo->{$metadata['indicador']});
            $this->assertTrue($metadata['requerido']);
            $this->assertContains('required', $configuracion['reglas'][$campo]);
            $this->assertMatchesRegularExpression('/[A-Za-zÁÉÍÓÚáéíóúÑñ]/u', $configuracion['etiquetas'][$campo]);
        }
    }

    public static function metodosParaConfiguracion(): array
    {
        return [
            'SAT fija' => [MetodoPago::INTERMEDIARIO_PAGOS],
            'SAT seleccionable' => [MetodoPago::DEPOSITO_BANCARIO],
            'sin adicionales' => [MetodoPago::EFECTIVO],
            'personalizado' => ['PERSONALIZADO'],
        ];
    }

    /** @dataProvider limitesTexto */
    public function test_limites_y_obligatoriedad_de_cada_campo_de_texto(string $indicador, string $campo, int $maximo): void
    {
        $metodo = MetodoPago::create(['clave' => 'LIMITE_'.strtoupper($campo), 'nombre' => 'Límite', $indicador => true]);
        foreach ([str_repeat('a', $maximo - 1), str_repeat('a', $maximo)] as $valido) {
            $resultado = $this->service->validarYNormalizarParaNuevoPago($metodo->getKey(), [$campo => $valido]);
            $this->assertSame($valido, $resultado['datos'][$campo]);
        }
        foreach ([str_repeat('a', $maximo + 1), '', '   '] as $invalido) {
            $this->assertValidationError($metodo, [$campo => $invalido], $campo);
        }
        $this->assertValidationError($metodo, [], $campo);
    }

    public static function limitesTexto(): array
    {
        return [
            'banco' => ['requiere_banco', 'banco', 120], 'referencia' => ['requiere_referencia', 'referencia', 120],
            'cheque' => ['requiere_numero_cheque', 'numero_cheque', 50], 'SPEI' => ['requiere_rastreo_spei', 'rastreo_spei', 100],
            'autorización' => ['requiere_autorizacion', 'numero_autorizacion', 100], 'terminal' => ['requiere_terminal', 'terminal', 100],
            'proveedor' => ['requiere_proveedor', 'proveedor', 120],
        ];
    }

    /** @dataProvider valoresUltimosCuatro */
    public function test_ultimos_cuatro_digitos_acepta_solo_cadena_exacta($valor, bool $valido): void
    {
        $metodo = MetodoPago::create(['clave' => 'CUATRO', 'nombre' => 'Cuatro', 'requiere_ultimos_4_digitos' => true]);
        $valido
            ? $this->assertSame((string) $valor, $this->service->validarYNormalizarParaNuevoPago($metodo->getKey(), ['ultimos_4_digitos' => $valor])['datos']['ultimos_4_digitos'])
            : $this->assertValidationError($metodo, ['ultimos_4_digitos' => $valor], 'ultimos_4_digitos');
    }

    public static function valoresUltimosCuatro(): array
    {
        return ['válido' => ['1234', true], 'tres' => ['123', false], 'cinco' => ['12345', false], 'letra' => ['12A4', false], 'vacío' => ['', false], 'espacios' => ['   ', false], 'entero corto' => [123, false]];
    }

    /** @dataProvider valoresFormaSat */
    public function test_forma_sat_seleccionable_acepta_solo_dos_digitos($valor, bool $valido): void
    {
        $metodo = MetodoPago::where('clave', MetodoPago::DEPOSITO_BANCARIO)->firstOrFail();
        $datos = ['banco' => 'Banco', 'referencia' => 'Referencia', 'comprobante' => 'archivo'];
        if ($valor !== '__ausente__') $datos['forma_pago_sat'] = $valor;
        $valido
            ? $this->assertSame('03', $this->service->validarYNormalizarParaNuevoPago($metodo->getKey(), $datos)['datos']['forma_pago_sat'])
            : $this->assertValidationError($metodo, $datos, 'forma_pago_sat');
    }

    public static function valoresFormaSat(): array
    {
        return ['válida' => ['03', true], 'un dígito' => ['3', false], 'tres dígitos' => ['003', false], 'letras' => ['AA', false], 'alfanumérica' => ['0A', false], 'vacía' => ['', false], 'espacios' => ['  ', false], 'ausente' => ['__ausente__', false]];
    }

    /** @dataProvider valoresAnticipo */
    public function test_anticipo_relacionado_valida_un_identificador_entero_positivo($valor, bool $valido): void
    {
        $metodo = MetodoPago::where('clave', MetodoPago::APLICACION_ANTICIPO)->firstOrFail();
        $datos = $valor === '__ausente__' ? [] : ['anticipo_relacionado_id' => $valor];
        $valido
            ? $this->assertEquals($valor, $this->service->validarYNormalizarParaNuevoPago($metodo->getKey(), $datos)['datos']['anticipo_relacionado_id'])
            : $this->assertValidationError($metodo, $datos, 'anticipo_relacionado_id');
    }

    public static function valoresAnticipo(): array
    {
        return ['entero' => [1, true], 'cadena numérica' => ['42', true], 'cero' => [0, false], 'negativo' => [-1, false], 'decimal' => [1.5, false], 'texto' => ['abc', false], 'ausente' => ['__ausente__', false]];
    }

    public function test_comprobante_expone_control_file_y_es_obligatorio(): void
    {
        $metodo = MetodoPago::create(['clave' => 'ARCHIVO', 'nombre' => 'Archivo', 'requiere_comprobante' => true]);
        $configuracion = $this->service->configuracion($metodo);
        $this->assertSame(['comprobante'], $this->service->camposObligatorios($metodo));
        $this->assertSame('file', $configuracion['campos']['comprobante']['control']);
        $this->assertValidationError($metodo, [], 'comprobante');
    }

    public function test_desactivacion_posterior_a_la_visualizacion_se_vuelve_a_consultar(): void
    {
        $mostrado = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $id = $mostrado->getKey();
        DB::table('metodos_pago')->where('metodo_pago_id', $id)->update(['activo' => false]);
        $this->assertTrue($mostrado->activo);
        $this->assertValidationErrorId($id);
    }

    public function test_cambios_de_metodo_conservan_solo_campos_nuevamente_aplicables(): void
    {
        $spei = ['banco' => ' Banco ', 'referencia' => ' REF ', 'rastreo_spei' => 'SPEI', 'comprobante' => 'archivo'];
        $efectivo = MetodoPago::where('clave', MetodoPago::EFECTIVO)->firstOrFail();
        $this->assertSame(['forma_pago_sat' => '01'], $this->service->validarYNormalizarParaNuevoPago($efectivo->getKey(), $spei)['datos']);

        $deposito = MetodoPago::where('clave', MetodoPago::DEPOSITO_BANCARIO)->firstOrFail();
        $depositoDatos = $spei + ['forma_pago_sat' => '03'];
        $this->assertSame(['forma_pago_sat' => '03', 'banco' => 'Banco', 'referencia' => 'REF', 'comprobante' => 'archivo'], $this->service->validarYNormalizarParaNuevoPago($deposito->getKey(), $depositoDatos)['datos']);

        $tarjeta = MetodoPago::where('clave', MetodoPago::TARJETA_DEBITO)->firstOrFail();
        $datosTarjeta = ['numero_autorizacion' => ' A ', 'terminal' => ' T ', 'ultimos_4_digitos' => '1234', 'proveedor' => 'oculto'];
        $this->assertSame(['numero_autorizacion' => 'A', 'terminal' => 'T', 'ultimos_4_digitos' => '1234', 'forma_pago_sat' => '28'], $this->service->validarYNormalizarParaNuevoPago($tarjeta->getKey(), $datosTarjeta)['datos']);

        $personalizado = MetodoPago::create(['clave' => 'CUSTOM', 'nombre' => 'Custom', 'requiere_banco' => true]);
        $original = ['banco' => ' Banco ', 'referencia' => 'descartar'];
        $this->assertSame(['banco' => 'Banco'], $this->service->validarYNormalizarParaNuevoPago($personalizado->getKey(), $original)['datos']);
        $this->assertSame(['banco' => ' Banco ', 'referencia' => 'descartar'], $original);
    }

    public function test_tres_modalidades_sat_son_inmutables_seleccionables_o_descartadas(): void
    {
        $fijo = MetodoPago::where('clave', MetodoPago::INTERMEDIARIO_PAGOS)->firstOrFail();
        foreach ([null, '', '99'] as $inyectada) {
            $resultado = $this->service->validarYNormalizarParaNuevoPago($fijo->getKey(), ['referencia' => 'R', 'proveedor' => 'P', 'forma_pago_sat' => $inyectada]);
            $this->assertSame('31', $resultado['datos']['forma_pago_sat']);
        }

        $deposito = MetodoPago::where('clave', MetodoPago::DEPOSITO_BANCARIO)->firstOrFail();
        $this->assertNull($deposito->clave_forma_pago_sat);
        $this->assertTrue($deposito->requiere_forma_pago_sat);
        $this->assertSame('DEPOSITO_BANCARIO', $deposito->clave);

        $sinSat = MetodoPago::create(['clave' => 'SIN_SAT_2', 'nombre' => 'Sin SAT']);
        $this->assertArrayNotHasKey('forma_pago_sat', $this->service->validarYNormalizarParaNuevoPago($sinSat->getKey(), ['forma_pago_sat' => '03'])['datos']);
    }

    private function assertValidationError(MetodoPago $metodo, array $datos, string $campo): void
    {
        try {
            $this->service->validarYNormalizarParaNuevoPago($metodo->getKey(), $datos);
            $this->fail("Debió fallar la validación de {$campo}.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($campo, $exception->errors());
        }
    }

    private function assertValidationErrorId($id): void
    {
        try {
            $this->service->validarYNormalizarParaNuevoPago($id, ['banco' => 'residual']);
            $this->fail('Debió rechazar el identificador.');
        } catch (ValidationException $exception) {
            $this->assertSame(['metodo_pago_id'], array_keys($exception->errors()));
        }
    }

    private function migration()
    {
        return require database_path('migrations/2026_09_08_000001_create_metodos_pago_table.php');
    }
}
