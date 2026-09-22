<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowAuditoriaPagos;
use App\Models\AuditoriaPago;
use Livewire\Livewire;
use Illuminate\Support\Facades\Blade;

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

    public function test_aside_only_shows_audit_link_to_authorized_user_and_marks_it_active(): void
    {
        $this->actingAs($this->user('admin'));
        request()->setRouteResolver(fn () => tap(new \Illuminate\Routing\Route('GET', '/facturacion/auditoria', []),
            fn ($route) => $route->name('facturacion.auditoria')));
        $aside = Blade::render('<x-layout.aside />');
        $this->assertStringContainsString(route('facturacion.auditoria'), $aside);
        $this->assertStringContainsString('Auditoría financiera', $aside);
        $this->assertStringContainsString('bg-blue-600 text-white', $aside);

        $this->actingAs($this->user('venta'));
        $this->assertStringNotContainsString(route('facturacion.auditoria'), Blade::render('<x-layout.aside />'));
        $this->get('/facturacion/auditoria')->assertForbidden();
    }
}
