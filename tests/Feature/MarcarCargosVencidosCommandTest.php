<?php

namespace Tests\Feature;

use App\Console\Kernel;
use App\Services\Facturacion\ActualizadorCargosVencidosService;
use Illuminate\Console\Scheduling\Schedule;
use Mockery;
use Tests\TestCase;

class MarcarCargosVencidosCommandTest extends TestCase
{
    public function test_el_comando_invoca_el_servicio_muestra_el_total_y_termina_correctamente(): void
    {
        $service = Mockery::mock(ActualizadorCargosVencidosService::class);
        $service->shouldReceive('actualizar')->once()->withNoArgs()->andReturn(15);
        $this->app->instance(ActualizadorCargosVencidosService::class, $service);

        $this->artisan('cargos:marcar-vencidos')
            ->expectsOutput('Cargos marcados como vencidos: 15')
            ->assertExitCode(0);
    }

    public function test_la_tarea_esta_registrada_una_sola_vez_diariamente_y_sin_solapamiento(): void
    {
        $schedule = new Schedule();
        $kernel = app(Kernel::class);
        $method = new \ReflectionMethod($kernel, 'schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);

        $events = collect($schedule->events())->filter(fn ($event) => str_contains($event->command, 'cargos:marcar-vencidos'));

        $this->assertCount(1, $events);
        $event = $events->first();
        $this->assertSame('10 0 * * *', $event->expression);
        $this->assertSame(config('app.timezone'), $event->timezone);
        $this->assertTrue($event->withoutOverlapping);
    }
}
