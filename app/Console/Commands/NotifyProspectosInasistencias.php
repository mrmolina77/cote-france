<?php

namespace App\Console\Commands;

use App\Models\Evaluacion;
use App\Models\Horario;
use App\Models\Prospecto;
use App\Notifications\AbsenceReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyProspectosInasistencias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prospectos:inasistencias-notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notifica a alumnos con 3 o más inasistencias registradas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoy = Carbon::today();

        $inasistentes = Evaluacion::query()
            ->where('evaluaciones.asistio', false)
            ->join('prospectos', 'evaluaciones.prospectos_id', '=', 'prospectos.prospectos_id')
            ->join('horarios', 'evaluaciones.horarios_id', '=', 'horarios.horarios_id')
            ->join('grupos', 'horarios.grupo_id', '=', 'grupos.grupo_id')
            ->select(
                'prospectos.prospectos_id',
                'prospectos.grupo_id',
                'grupos.grupo_nombre'
            )
            ->selectRaw('COUNT(*) as total_inasistencias')
            ->groupBy(
                'prospectos.prospectos_id',
                'prospectos.grupo_id',
                'grupos.grupo_nombre'
            )
            ->having('total_inasistencias', '>=', 3)
            ->orderByDesc('total_inasistencias')
            ->get();

        $notificados = 0;

        foreach ($inasistentes as $inasistente) {
            $prospecto = Prospecto::find($inasistente->prospectos_id);

            if (! $prospecto || ! $prospecto->prospectos_correo) {
                continue;
            }

            $proximaClase = null;

            if ($inasistente->grupo_id) {
                $proximaClase = Horario::with('hora')
                    ->where('grupo_id', $inasistente->grupo_id)
                    ->whereDate('horarios_dia', '>=', $hoy)
                    ->orderBy('horarios_dia')
                    ->orderBy('horas_id')
                    ->first();
            }

            $prospecto->notify(new AbsenceReminder(
                $prospecto,
                $inasistente->grupo_nombre,
                $inasistente->total_inasistencias,
                $proximaClase
            ));

            $notificados++;
            usleep(125000);
        }

        $this->info("Notificaciones enviadas: {$notificados}");

        return Command::SUCCESS;
    }
}
