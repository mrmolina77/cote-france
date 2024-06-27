<?php

namespace Database\Seeders;

use App\Models\EstatuTarea;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstatuTareaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        EstatuTarea::create([
            'est_tareas_descripcion' =>'Pendiente',
        ]);
        EstatuTarea::create([
            'est_tareas_descripcion' =>'Realizado',
        ]);
        EstatuTarea::create([
            'est_tareas_descripcion' =>'No realizado',
        ]);
    }
}
