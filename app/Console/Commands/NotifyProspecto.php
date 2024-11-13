<?php

namespace App\Console\Commands;

use App\Models\Prospecto;
use App\Notifications\ClassReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotifyProspecto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prostecto:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // return Command::SUCCESS;
        $hoy = Carbon::today();
        $prospectos = DB::table('prospectos')
                        ->select('prospectos.prospectos_id')
                        ->join('horarios','horarios.horarios_id','=','prospectos.horarios_id')
                        ->whereDate('horarios.horarios_dia', $hoy)
                        ->get();
        foreach ($prospectos as $prospecto) {
            $prospe = Prospecto::find($prospecto->prospectos_id);
            $prospe->notify(new ClassReminder($prospe));
        }
    }
}
