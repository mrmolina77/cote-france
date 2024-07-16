<?php

namespace Database\Seeders;

use App\Models\Curso;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CursoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Curso::create([
            'cursos_descripcion' =>'A1 Principiante',
            'cursos_fecha_creacion' => date('Y-m-d'),
            'cursos_activo' => true,
        ]);
        Curso::create([
            'cursos_descripcion' =>'A2 Elemento',
            'cursos_fecha_creacion' => date('Y-m-d'),
            'cursos_activo' => true,
        ]);
        Curso::create([
            'cursos_descripcion' =>'A3 Intermadio',
            'cursos_fecha_creacion' => date('Y-m-d'),
            'cursos_activo' => true,
        ]);
        Curso::create([
            'cursos_descripcion' =>'A4 Avanzado',
            'cursos_fecha_creacion' => date('Y-m-d'),
            'cursos_activo' => true,
        ]);
    }
}
