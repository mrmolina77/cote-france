<?php

namespace Database\Seeders;

use App\Models\Dia;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Dia::create([
            'dias_nombre' =>'Domingo'
        ]);
        Dia::create([
            'dias_nombre' =>'Lunes'
        ]);
        Dia::create([
            'dias_nombre' =>'Martes'
        ]);
        Dia::create([
            'dias_nombre' =>'Miércoles'
        ]);
        Dia::create([
            'dias_nombre' =>'Jueves'
        ]);
        Dia::create([
            'dias_nombre' =>'Viernes'
        ]);
        Dia::create([
            'dias_nombre' =>'Sábado'
        ]);
    }
}
