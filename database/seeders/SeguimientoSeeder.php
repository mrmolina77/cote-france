<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Seguimiento;

class SeguimientoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Seguimiento::create([
            'seguimientos_descripcion' =>'Contactar',
        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'Viene a clase de prueba',
        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'Falto a clase de prueba',
        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'Vinó a clase',
        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'Espera Grupo',
        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'Esperar',
        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'No contesta',
        ]);
    }
}
