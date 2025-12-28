<?php

namespace App\Console\Commands;

use App\Models\Horario;
use App\Notifications\DiariosPendientesProfesor;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyProfesoresDiariosPendientes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profesores:diarios-pendientes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notifica a profesores con clases pasadas sin diario actualizado';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $hoy = Carbon::today();

        $horarios = Horario::with(['profesor', 'hora', 'grupo'])
            ->whereDate('horarios_dia', '<', $hoy)
            ->doesntHave('diario')
            ->orderBy('horarios_dia')
            ->orderBy('horas_id')
            ->get()
            ->groupBy('profesores_id');

        $notificados = 0;

        foreach ($horarios as $clases) {
            $profesor = $clases->first()?->profesor;

            if (! $profesor) {
                continue;
            }

            $detalleClases = $clases->map(function ($horario) {
                return [
                    'dia' => Carbon::parse($horario->horarios_dia)->format('d-m-Y'),
                    'hora' => $horario->hora?->horas_desde,
                    'grupo' => $horario->grupo?->grupo_nombre,
                ];
            })->values()->all();

            $profesor->notify(new DiariosPendientesProfesor($detalleClases));
            $notificados++;
            usleep(125000);
        }

        $this->info("Notificaciones enviadas: {$notificados}");

        return Command::SUCCESS;
    }
}
