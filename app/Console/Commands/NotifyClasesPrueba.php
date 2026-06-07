<?php

namespace App\Console\Commands;

use App\Models\ClasePrueba;
use App\Notifications\ClassReminder;
use App\Notifications\TeacherClassReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyClasesPrueba extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clases-prueba:notify {--date= : Fecha específica para buscar en formato YYYY-MM-DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un correo recordatorio al maestro y al alumno un día anterior a la clase de prueba';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tomorrow = $this->option('date') ?: Carbon::tomorrow()->toDateString();

        $clasesPrueba = ClasePrueba::query()
            ->whereDate('horarios_dia', $tomorrow)
            ->where('estado', '!=', 'cancelada')
            ->with(['prospecto', 'profesor', 'hora', 'espacio', 'modalidad', 'horario.profesor', 'horario.espacio', 'horario.hora', 'grupo.modalidad'])
            ->get();

        $this->info("Buscando clases de prueba programadas para: {$tomorrow}");
        $this->info("Total encontradas: " . $clasesPrueba->count());

        $prospectosNotificados = 0;
        $profesoresNotificados = 0;

        foreach ($clasesPrueba as $clasePrueba) {
            // Resolve relationships from horario/grupo if they are null on the ClasePrueba itself
            if (!$clasePrueba->profesor && $clasePrueba->horario) {
                $clasePrueba->setRelation('profesor', $clasePrueba->horario->profesor);
            }
            if (!$clasePrueba->espacio && $clasePrueba->horario) {
                $clasePrueba->setRelation('espacio', $clasePrueba->horario->espacio);
            }
            if (!$clasePrueba->hora && $clasePrueba->horario) {
                $clasePrueba->setRelation('hora', $clasePrueba->horario->hora);
            }
            if (!$clasePrueba->modalidad) {
                if ($clasePrueba->grupo) {
                    $clasePrueba->setRelation('modalidad', $clasePrueba->grupo->modalidad);
                } elseif ($clasePrueba->horario && $clasePrueba->horario->grupo) {
                    $clasePrueba->setRelation('modalidad', $clasePrueba->horario->grupo->modalidad);
                }
            }

            // 1. Notify the prospect (student/alumno)
            $prospecto = $clasePrueba->prospecto;
            if ($prospecto && $prospecto->prospectos_correo) {
                try {
                    $prospecto->notify(new ClassReminder($clasePrueba));
                    $prospectosNotificados++;
                    $this->info("-> Notificado alumno: {$prospecto->prospectos_nombres} ({$prospecto->prospectos_correo})");
                } catch (\Exception $e) {
                    Log::error("Error al notificar al alumno de clase de prueba ID {$clasePrueba->clase_prueba_id}: " . $e->getMessage());
                    $this->error("Error al notificar alumno ID {$prospecto->prospectos_id}: " . $e->getMessage());
                }
            } else {
                $this->warn("-> Alumno sin correo válido o no existente para clase de prueba ID {$clasePrueba->clase_prueba_id}");
            }

            // 2. Notify the teacher (maestro)
            $profesor = $clasePrueba->profesor;
            if ($profesor && $profesor->profesores_email) {
                try {
                    $profesor->notify(new TeacherClassReminder($clasePrueba));
                    $profesoresNotificados++;
                    $this->info("-> Notificado profesor: {$profesor->profesores_nombres} ({$profesor->profesores_email})");
                } catch (\Exception $e) {
                    Log::error("Error al notificar al profesor de clase de prueba ID {$clasePrueba->clase_prueba_id}: " . $e->getMessage());
                    $this->error("Error al notificar profesor ID {$profesor->profesores_id}: " . $e->getMessage());
                }
            } else {
                $this->warn("-> Profesor no asignado o sin correo válido para clase de prueba ID {$clasePrueba->clase_prueba_id}");
            }

            // Small throttle to avoid hitting mail server limits
            usleep(100000); // 100ms
        }

        $this->info("----------------------------------------");
        $this->info("Resumen:");
        $this->info("- Alumnos notificados: {$prospectosNotificados}");
        $this->info("- Profesores notificados: {$profesoresNotificados}");

        return Command::SUCCESS;
    }
}
