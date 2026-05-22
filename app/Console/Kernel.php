<?php

namespace App\Console;

use App\Console\Commands\NotifyProfesoresDiariosPendientes;
use App\Console\Commands\NotifyProspecto;
use App\Console\Commands\NotifyProspectosInasistencias;
use App\Console\Commands\NotifyClasesPrueba;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        NotifyProspecto::class,
        NotifyProfesoresDiariosPendientes::class,
        NotifyProspectosInasistencias::class,
        NotifyClasesPrueba::class,
      ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('prostecto:notify')->dailyAt('22:33');
        $schedule->command('profesores:diarios-pendientes')->dailyAt('06:00');
        $schedule->command('prospectos:inasistencias-notify')->dailyAt('07:00');
        $schedule->command('clases-prueba:notify')->dailyAt('08:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
