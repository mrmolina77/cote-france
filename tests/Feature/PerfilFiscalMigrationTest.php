<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerfilFiscalMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_has_profile_payment_link_and_append_only_audit_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('perfiles_fiscales', ['perfil_fiscal_id', 'prospectos_id', 'rfc', 'predeterminado', 'activo', 'fecha_validacion']));
        $this->assertTrue(Schema::hasColumns('pagos', ['solicita_factura', 'perfil_fiscal_id', 'perfil_fiscal_snapshot']));
        $this->assertTrue(Schema::hasColumns('auditoria_perfiles_fiscales', ['perfil_fiscal_id', 'prospectos_id', 'usuario_id', 'accion', 'campos_modificados', 'ocurrido_en']));

        $columns = collect(DB::select("PRAGMA table_info('pagos')"))->keyBy('name');
        $this->assertSame(0, (int) $columns['perfil_fiscal_id']->notnull);
        $this->assertContains((string) $columns['solicita_factura']->dflt_value, ['0', "'0'"]);
        if (DB::getDriverName() !== 'sqlite') {
            $foreign = collect(DB::select("PRAGMA foreign_key_list('pagos')"));
            $this->assertTrue($foreign->contains(fn ($fk) => $fk->from === 'perfil_fiscal_id' && $fk->table === 'perfiles_fiscales' && strtoupper($fk->on_delete) === 'RESTRICT'));
        }
    }

    public function test_down_removes_audit_table_profile_table_and_all_payment_columns(): void
    {
        (require database_path('migrations/2026_09_28_000002_create_auditoria_perfiles_fiscales_table.php'))->down();
        (require database_path('migrations/2026_09_28_000001_create_perfiles_fiscales_and_link_pagos.php'))->down();

        $this->assertFalse(Schema::hasTable('auditoria_perfiles_fiscales'));
        $this->assertFalse(Schema::hasTable('perfiles_fiscales'));
        $this->assertFalse(Schema::hasColumn('pagos', 'solicita_factura'));
        $this->assertFalse(Schema::hasColumn('pagos', 'perfil_fiscal_id'));
        $this->assertFalse(Schema::hasColumn('pagos', 'perfil_fiscal_snapshot'));

        // Restore the schema so subsequent tests do not fail due to missing tables
        (require database_path('migrations/2026_09_28_000001_create_perfiles_fiscales_and_link_pagos.php'))->up();
        (require database_path('migrations/2026_09_28_000002_create_auditoria_perfiles_fiscales_table.php'))->up();
    }
}
