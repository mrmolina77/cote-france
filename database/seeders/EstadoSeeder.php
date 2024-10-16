<?php

namespace Database\Seeders;

use App\Models\Estado;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Estado::create([
            'estado_nombre' =>'Estado 1',
            'estado_descripcion' =>'Descripción estado 1',
        ]);
        Estado::create([
            'estado_nombre' =>'Estado 2',
            'estado_descripcion' =>'Descripción estado 2',
        ]);
        Estado::create([
            'estado_nombre' =>'Estado 3',
            'estado_descripcion' =>'Descripción estado 3',
        ]);
        Estado::create([
            'estado_nombre' =>'Estado 4',
            'estado_descripcion' =>'Descripción estado 4',
        ]);

    }
}
