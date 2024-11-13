<?php

namespace Database\Seeders;

use App\Models\Hora;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HoraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Hora::create([
            'horas_desde' =>'08:00:00',
            'horas_hasta' =>'09:30:00',
        ]);
        Hora::create([
            'horas_desde' =>'09:30:00',
            'horas_hasta' =>'11:00:00',
        ]);
        Hora::create([
            'horas_desde' =>'11:00:00',
            'horas_hasta' =>'12:30:00',
        ]);
        Hora::create([
            'horas_desde' =>'12:30:00',
            'horas_hasta' =>'14:00:00',
        ]);
        Hora::create([
            'horas_desde' =>'14:00:00',
            'horas_hasta' =>'15:30:00',
        ]);
        Hora::create([
            'horas_desde' =>'15:30:00',
            'horas_hasta' =>'17:00:00',
        ]);
        Hora::create([
            'horas_desde' =>'17:00:00',
            'horas_hasta' =>'18:30:00',
        ]);
        Hora::create([
            'horas_desde' =>'18:30:00',
            'horas_hasta' =>'20:00:00',
        ]);
        Hora::create([
            'horas_desde' =>'20:00:00',
            'horas_hasta' =>'21:30:00',
        ]);
    }
}
