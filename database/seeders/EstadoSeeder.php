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
            'estado_nombre' =>'Activo',
            'estado_descripcion' =>'Descripción activo',
        ]);
        Estado::create([
            'estado_nombre' =>'Inactivo',
            'estado_descripcion' =>'Descripción inactivo',
        ]);
    }
}
