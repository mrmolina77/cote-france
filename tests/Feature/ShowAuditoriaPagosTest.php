<?php

namespace Tests\Feature;

use App\Http\Livewire\ShowAuditoriaPagos;
use App\Models\AuditoriaPago;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\ResponsablePago;
use App\Services\Facturacion\AuditoriaPagoService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Illuminate\Support\Facades\Blade;

class ShowAuditoriaPagosTest extends InscripcionesTestCase
{
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

    public function test_detail_rejects_non_integer_and_missing_identifiers(): void
    {
        $admin = $this->user('admin');

        foreach (['texto', '1.5', 0, -1, 999999] as $id) {
            Livewire::actingAs($admin)->test(ShowAuditoriaPagos::class)
                ->call('verDetalle', $id)
                ->assertNotFound();
        }
    }

    public function test_filters_detail_close_pagination_and_stable_order(): void
    {
        $admin = $this->user('admin');
        [$pagoUno, $eventoUno] = $this->evento('PAG-FILTRO-001', 'Ana', 'Zulu', AuditoriaPago::CREAR, $admin->getKey(), '2026-09-20 10:00:00');
        [$pagoDos, $eventoDos] = $this->evento('PAG-FILTRO-002', 'Bruno', 'Alfa', AuditoriaPago::CANCELAR, null, '2026-09-21 10:00:00');

        Livewire::actingAs($admin)->test(ShowAuditoriaPagos::class)
            ->set('busqueda', $pagoUno->folio)->assertSee($pagoUno->folio)->assertDontSee($pagoDos->folio)
            ->set('busqueda', (string) $pagoUno->inscripciones_id)->assertSee($pagoUno->folio)->assertDontSee($pagoDos->folio)
            ->set('busqueda', 'Ana')->assertSee($pagoUno->folio)->assertDontSee($pagoDos->folio)
            ->set('busqueda', 'Zulu')->assertSee($pagoUno->folio)->assertDontSee($pagoDos->folio)
            ->set('busqueda', '')->set('accion', AuditoriaPago::CANCELAR)->assertSee($pagoDos->folio)->assertDontSee($pagoUno->folio)
            ->set('accion', '')->set('usuarioId', (string) $admin->getKey())->assertSee($pagoUno->folio)->assertDontSee($pagoDos->folio)
            ->set('usuarioId', '')->set('fechaDesde', '2026-09-21')->assertSee($pagoDos->folio)->assertDontSee($pagoUno->folio)
            ->set('fechaDesde', '')->set('fechaHasta', '2026-09-20')->assertSee($pagoUno->folio)->assertDontSee($pagoDos->folio)
            ->set('fechaDesde', '2026-09-22')->assertSee('El rango de fechas no es válido.')->assertSee('No hay eventos')
            ->set('fechaDesde', '')->set('fechaHasta', '')->call('verDetalle', $eventoUno->getKey())
            ->assertSet('detalleId', $eventoUno->getKey())->assertSee('Valores anteriores')->assertSee('Valores nuevos')->assertSee('Metadatos')
            ->call('cerrarDetalle')->assertSet('detalleId', null);

        for ($i = 3; $i <= 12; $i++) {
            $this->evento(sprintf('PAG-PAGINA-%03d', $i), 'Alumno', (string) $i, AuditoriaPago::CREAR, null, '2026-09-22 10:00:00');
        }
        Livewire::actingAs($admin)->test(ShowAuditoriaPagos::class)
            ->set('porPagina', 10)->assertSee('PAG-PAGINA-012')->assertDontSee($pagoUno->folio)
            ->call('gotoPage', 2)->assertSee($pagoUno->folio)
            ->set('busqueda', 'PAG-FILTRO-002')->assertSet('page', 1)->assertSee($pagoDos->folio)->assertDontSee('PAG-PAGINA-012');
    }

    public function test_audit_detail_escapes_xss_payload(): void
    {
        $admin = $this->user('admin');
        [, $evento] = $this->evento("<script>alert('xss')</script>", 'Xss', 'Seguro', AuditoriaPago::CREAR, $admin->getKey(), '2026-09-23 10:00:00');

        $html = Livewire::actingAs($admin)->test(ShowAuditoriaPagos::class)
            ->call('verDetalle', $evento->getKey())->html();

        $this->assertStringNotContainsString("<script>alert('xss')</script>", $html);
        $this->assertStringContainsString('&lt;script&gt;alert', $html);
    }

    private function evento(string $folio, string $nombre, string $apellido, string $accion, ?int $usuarioId, string $fecha): array
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $prospecto->update(['prospectos_nombres' => $nombre, 'prospectos_apellidos' => $apellido]);
        $responsable = ResponsablePago::create(['tipo' => 'persona', 'prospectos_id' => $prospecto->getKey(),
            'nombre_razon_social' => $nombre.' '.$apellido, 'activo' => true]);
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $inscripcion->update(['responsable_pago_id' => $responsable->getKey()]);
        $pago = Pago::forceCreate([
            'folio' => $folio, 'inscripciones_id' => $inscripcion->getKey(), 'prospectos_id' => $prospecto->getKey(),
            'responsable_pago_id' => $responsable->getKey(), 'fecha_pago' => $fecha, 'zona_horaria' => 'UTC',
            'moneda' => 'MXN', 'monto' => '1.00', 'metodo_pago_id' => MetodoPago::firstOrFail()->getKey(),
            'estado' => Pago::ESTADO_CONFIRMADO,
        ]);
        $evento = app(AuditoriaPagoService::class)->registrar($pago, $accion, $usuarioId, [], ['folio' => $folio], ['motivo' => $folio]);
        DB::table('auditoria_pagos')->where('auditoria_pago_id', $evento->getKey())->update(['ocurrido_en' => $fecha]);

        return [$pago, $evento->fresh()];
    }
}
