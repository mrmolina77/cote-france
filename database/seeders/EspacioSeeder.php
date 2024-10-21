<?php

namespace Database\Seeders;

use App\Models\Espacio;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EspacioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Espacio::create([
            'espacios_nombre' =>'Salon 1',
            'espacios_descripcion' =>'Descripción del salon 1',
            'espacios_activo' =>true,
            'modalidad_id' =>'1',
        ]);
        Espacio::create([
            'espacios_nombre' =>'Salon virtual 1',
            'espacios_descripcion' =>'Descripción del salon virtual 1',
            'espacios_activo' =>true,
            'modalidad_id' =>'2',
        ]);
        Espacio::create([
            'espacios_nombre' =>'Salon 2',
            'espacios_descripcion' =>'Descripción del salon 2',
            'espacios_activo' =>true,
            'modalidad_id' =>'1',
        ]);
    }
}
