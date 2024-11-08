<?php

namespace Database\Seeders;

use App\Models\Modalidad;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModalidadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Modalidad::create([
            'modalidad_nombre' =>'Presencial',
            'modalidad_descripcion' =>'Descripción Presencial',
        ]);
        Modalidad::create([
            'modalidad_nombre' =>'En linea',
            'modalidad_descripcion' =>'Descripción en linea',
        ]);
        Modalidad::create([
            'modalidad_nombre' =>'Mixto',
            'modalidad_descripcion' =>'Descripción mixto',
        ]);
    }
}
