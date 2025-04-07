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
            'seguimientos_codigo' =>'cnt',
        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'Viene a clase de prueba',
            'seguimientos_codigo' =>'vcp',
        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'Falto a clase de prueba',
            'seguimientos_codigo' =>'fcp',
        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'Vinó a clase',
            'seguimientos_codigo' =>'vcl',
        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'Espera Grupo',
            'seguimientos_codigo' =>'egr',
        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'Esperar',
            'seguimientos_codigo' =>'esp',

        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'No contesta',
            'seguimientos_codigo' =>'nco',
        ]);
        Seguimiento::create([
            'seguimientos_descripcion' =>'Concretado',
            'seguimientos_codigo' =>'cnc',
        ]);
    }
}
