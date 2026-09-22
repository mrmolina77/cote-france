<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowAuditoriaPagos;
use App\Models\AuditoriaPago;
use Livewire\Livewire;

class ShowAuditoriaPagosTest extends InscripcionesTestCase
{
    protected function setUp(): void { parent::setUp(); (require database_path('migrations/2026_09_22_000003_create_auditoria_pagos_table.php'))->up(); }

    public function test_route_and_livewire_are_protected(): void
    {
        $this->get('/facturacion/auditoria')->assertRedirect('/login');
        $usuario = $this->user('venta');
        $this->actingAs($usuario)->get('/facturacion/auditoria')->assertForbidden();
        Livewire::actingAs($usuario)->test(ShowAuditoriaPagos::class)->assertForbidden();
    }

    public function test_admin_can_open_empty_audit_view(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->get('/facturacion/auditoria')->assertOk()->assertSee('Auditoría financiera');
        Livewire::actingAs($admin)->test(ShowAuditoriaPagos::class)->set('accion', AuditoriaPago::CONFIRMAR)
            ->assertSee('No hay eventos');
    }
}
