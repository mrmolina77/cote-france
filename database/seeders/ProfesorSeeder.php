<?php

namespace Database\Seeders;

use App\Models\Profesor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfesorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Profesor::create([
            'profesores_nombres' =>'Jose',
            'profesores_apellidos' =>'Lopez',
            'profesores_email' =>'jlopez@gmail.com',
            'profesores_fecha_ingreso' =>date('Y-m-d'),
        ]);
        Profesor::create([
            'profesores_nombres' =>'Maria',
            'profesores_apellidos' =>'Perez',
            'profesores_email' =>'mperez@gmail.com',
            'profesores_fecha_ingreso' =>date('Y-m-d'),
        ]);
        Profesor::create([
            'profesores_nombres' =>'Juan',
            'profesores_apellidos' =>'Garcias',
            'profesores_email' =>'jgarcias@gmail.com',
            'profesores_fecha_ingreso' =>date('Y-m-d'),
        ]);
        Profesor::create([
            'profesores_nombres' =>'Fracisco',
            'profesores_apellidos' =>'Matas',
            'profesores_email' =>'fmatas@gmail.com',
            'profesores_fecha_ingreso' =>date('Y-m-d'),
        ]);
    }
}
