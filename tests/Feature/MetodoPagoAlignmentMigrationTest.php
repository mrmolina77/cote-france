<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetodoPagoAlignmentMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        (require database_path('migrations/2026_09_08_000001_create_metodos_pago_table.php'))->up();
    }

    public function test_up_alinea_exclusivamente_el_estado_anterior_completo(): void
    {
        $intermediario = MetodoPago::create($this->configuracionCanonicaAnteriorIntermediario());
        $antes = $this->atributosCanonicos($intermediario);

        $this->migration()->up();
        $intermediario->refresh();

        $esperado = $antes;
        $esperado['clave_forma_pago_sat'] = '31';
        $esperado['requiere_forma_pago_sat'] = false;
        $this->assertSame($esperado, $this->atributosCanonicos($intermediario));
    }

    /** @dataProvider personalizacionesEstadoAnterior */
    public function test_up_protege_cada_personalizacion_administrativa(string $atributo, $valor): void
    {
        $estado = $this->configuracionCanonicaAnteriorIntermediario();
        $estado[$atributo] = $valor;
        $intermediario = MetodoPago::create($estado);
        $antes = $this->atributosCanonicos($intermediario);

        $this->migration()->up();
        $intermediario->refresh();

        $this->assertSame($antes, $this->atributosCanonicos($intermediario));
    }

    public static function personalizacionesEstadoAnterior(): array
    {
        return [
            'nombre' => ['nombre', 'Nombre administrativo'],
            'descripcion' => ['descripcion', 'Configuración local'],
            'orden' => ['orden', 101],
            'inactivo' => ['activo', false],
            'captura SAT desactivada' => ['requiere_forma_pago_sat', false],
            'banco' => ['requiere_banco', true],
            'referencia' => ['requiere_referencia', false],
            'cheque' => ['requiere_numero_cheque', true],
            'SPEI' => ['requiere_rastreo_spei', true],
            'autorización' => ['requiere_autorizacion', true],
            'terminal' => ['requiere_terminal', true],
            'últimos cuatro' => ['requiere_ultimos_4_digitos', true],
            'proveedor' => ['requiere_proveedor', false],
            'anticipo' => ['requiere_anticipo_relacionado', true],
            'comprobante' => ['requiere_comprobante', true],
            'SAT previamente configurada' => ['clave_forma_pago_sat', '99'],
        ];
    }

    public function test_down_revierte_exclusivamente_el_estado_nuevo_completo(): void
    {
        $intermediario = MetodoPago::create($this->configuracionCanonicaNuevaIntermediario());
        $this->migration()->down();
        $intermediario->refresh();
        $this->assertSame($this->configuracionCanonicaAnteriorIntermediario(), $this->atributosCanonicos($intermediario));
    }

    /** @dataProvider personalizacionesEstadoNuevo */
    public function test_down_protege_configuraciones_administrativas_posteriores(array $personalizacion): void
    {
        $estado = array_replace($this->configuracionCanonicaNuevaIntermediario(), $personalizacion);
        $intermediario = MetodoPago::create($estado);
        $antes = $this->atributosCanonicos($intermediario);

        $this->migration()->down();
        $intermediario->refresh();

        $this->assertSame($antes, $this->atributosCanonicos($intermediario));
    }

    public static function personalizacionesEstadoNuevo(): array
    {
        return [
            'nombre' => [['nombre' => 'Nombre posterior']],
            'descripción' => [['descripcion' => 'Decisión administrativa']],
            'orden' => [['orden' => 999]],
            'desactivado' => [['activo' => false]],
            'indicador dinámico' => [['requiere_comprobante' => true]],
            'SAT diferente' => [['clave_forma_pago_sat' => '99']],
            'captura SAT reactivada' => [['requiere_forma_pago_sat' => true]],
            'combinación posterior' => [['nombre' => 'Otro', 'activo' => false, 'requiere_banco' => true, 'clave_forma_pago_sat' => '03']],
        ];
    }

    public function test_up_y_down_son_simetricos_para_todos_los_atributos_canonicos(): void
    {
        $intermediario = MetodoPago::create($this->configuracionCanonicaAnteriorIntermediario());
        $anterior = $this->atributosCanonicos($intermediario);
        $migration = $this->migration();

        $migration->up();
        $intermediario->refresh();
        $this->assertSame($this->configuracionCanonicaNuevaIntermediario(), $this->atributosCanonicos($intermediario));

        $migration->down();
        $intermediario->refresh();
        $this->assertSame($anterior, $this->atributosCanonicos($intermediario));
    }

    public function test_migracion_usa_literal_historico_y_no_depende_del_modelo(): void
    {
        $contenido = file_get_contents(database_path('migrations/2026_09_09_000001_align_intermediario_pagos_sat_key.php'));
        $this->assertStringContainsString("'INTERMEDIARIO_PAGOS'", $contenido);
        $this->assertStringNotContainsString('App\\Models\\MetodoPago', $contenido);
        $this->assertDoesNotMatchRegularExpression('/MetodoPago::[A-Z_]+/', $contenido);
    }

    private function configuracionCanonicaAnteriorIntermediario(): array
    {
        return [
            'clave' => 'INTERMEDIARIO_PAGOS', 'nombre' => 'Intermediario de pagos', 'descripcion' => null,
            'clave_forma_pago_sat' => null, 'requiere_forma_pago_sat' => true, 'requiere_banco' => false,
            'requiere_referencia' => true, 'requiere_numero_cheque' => false, 'requiere_rastreo_spei' => false,
            'requiere_autorizacion' => false, 'requiere_terminal' => false, 'requiere_ultimos_4_digitos' => false,
            'requiere_proveedor' => true, 'requiere_anticipo_relacionado' => false, 'requiere_comprobante' => false,
            'activo' => true, 'orden' => 100,
        ];
    }

    private function configuracionCanonicaNuevaIntermediario(): array
    {
        return array_replace($this->configuracionCanonicaAnteriorIntermediario(), [
            'clave_forma_pago_sat' => '31', 'requiere_forma_pago_sat' => false,
        ]);
    }

    private function atributosCanonicos(MetodoPago $metodo): array
    {
        return $metodo->only(array_keys($this->configuracionCanonicaAnteriorIntermediario()));
    }

    private function migration()
    {
        return require database_path('migrations/2026_09_09_000001_align_intermediario_pagos_sat_key.php');
    }
}
