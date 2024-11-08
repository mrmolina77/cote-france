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
            'profesores_horas_semanales' =>'8',
            'profesores_color' =>'#e81111',
        ]);
        Profesor::create([
            'profesores_nombres' =>'Maria',
            'profesores_apellidos' =>'Perez',
            'profesores_email' =>'mperez@gmail.com',
            'profesores_fecha_ingreso' =>date('Y-m-d'),
            'profesores_horas_semanales' =>'10',
            'profesores_color' =>'#29219c',
        ]);
        Profesor::create([
            'profesores_nombres' =>'Juan',
            'profesores_apellidos' =>'Garcias',
            'profesores_email' =>'jgarcias@gmail.com',
            'profesores_fecha_ingreso' =>date('Y-m-d'),
            'profesores_horas_semanales' =>'12',
            'profesores_color' =>'#359f0f',
        ]);
        Profesor::create([
            'profesores_nombres' =>'Fracisco',
            'profesores_apellidos' =>'Matas',
            'profesores_email' =>'fmatas@gmail.com',
            'profesores_fecha_ingreso' =>date('Y-m-d'),
            'profesores_horas_semanales' =>'14',
            'profesores_color' =>'#e1ff00',
        ]);
    }
}
