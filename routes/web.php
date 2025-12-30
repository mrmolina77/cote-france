<?php

use App\Http\Livewire\ShowAsistencias;
use App\Http\Livewire\ShowProfesores;
use App\Http\Livewire\ShowGrupos;
use App\Http\Livewire\ShowHorarios;
use App\Http\Livewire\ShowInscripciones;
use App\Http\Livewire\ShowProgramadas;
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\ShowProspectos;
use App\Http\Livewire\ShowTareas;
use App\Http\Livewire\ShowUsuarios;
use App\Http\Livewire\ShowEspacios;
use App\Http\Livewire\ShowProfesorHorario;
use App\Models\Diario;
use App\Models\Evaluacion;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route::redirect('/', '/dashboard', 301);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        $recentHorarioIds = Diario::query()
            ->whereNotNull('horarios_id')
            ->orderByDesc('updated_at')
            ->limit(3)
            ->pluck('horarios_id')
            ->unique()
            ->values();

        $inasistentes = collect();
        if ($recentHorarioIds->isNotEmpty()) {
            $inasistentes = Evaluacion::query()
                ->whereIn('evaluaciones.horarios_id', $recentHorarioIds)
                ->where('evaluaciones.asistio', false)
                ->join('prospectos', 'evaluaciones.prospectos_id', '=', 'prospectos.prospectos_id')
                ->join('horarios', 'evaluaciones.horarios_id', '=', 'horarios.horarios_id')
                ->join('grupos', 'horarios.grupo_id', '=', 'grupos.grupo_id')
                ->select(
                    'prospectos.prospectos_nombres',
                    'prospectos.prospectos_apellidos',
                    'grupos.grupo_nombre',
                    'horarios.horarios_id'
                )
                ->orderByDesc('horarios.horarios_id')
                ->get();
        }

        return view('dashboard', [
            'inasistentes' => $inasistentes,
        ]);
    })->name('dashboard');
});
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->get('/prospectos', ShowProspectos::class )->name('prospectos');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->get('/tareas', ShowTareas::class )->name('tareas');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->get('/clasespruebas', ShowProfesores::class )->name('clasespruebas');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->get('/asistencias', ShowAsistencias::class )->name('asistencias');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->get('/inscripciones', ShowInscripciones::class )->name('inscripciones');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->get('/usuarios', ShowUsuarios::class )->name('usuarios');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->get('/programadas', ShowProgramadas::class )->name('programadas');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->get('/grupos', ShowGrupos::class )->name('grupos');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->get('/profesores', ShowProfesores::class )->name('profesores');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->get('/horarios/{modalidad}', ShowHorarios::class )->name('horarios');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->get('/horario_profesor/{modalidad}', ShowProfesorHorario::class )->name('horario_profesor');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->get('/espacios', ShowEspacios::class )->name('espacios');
