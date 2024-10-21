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
            'horas_desde' =>'08:05:00',
            'horas_hasta' =>'08:50:00',
        ]);
        Hora::create([
            'horas_desde' =>'09:05:00',
            'horas_hasta' =>'09:50:00',
        ]);
        Hora::create([
            'horas_desde' =>'10:05:00',
            'horas_hasta' =>'10:50:00',
        ]);
        Hora::create([
            'horas_desde' =>'11:05:00',
            'horas_hasta' =>'11:50:00',
        ]);
        Hora::create([
            'horas_desde' =>'13:05:00',
            'horas_hasta' =>'13:50:00',
        ]);
        Hora::create([
            'horas_desde' =>'14:05:00',
            'horas_hasta' =>'14:50:00',
        ]);
        Hora::create([
            'horas_desde' =>'15:05:00',
            'horas_hasta' =>'15:50:00',
        ]);
        Hora::create([
            'horas_desde' =>'16:05:00',
            'horas_hasta' =>'16:50:00',
        ]);
        Hora::create([
            'horas_desde' =>'17:05:00',
            'horas_hasta' =>'17:50:00',
        ]);
        Hora::create([
            'horas_desde' =>'18:05:00',
            'horas_hasta' =>'18:50:00',
        ]);
    }
}
