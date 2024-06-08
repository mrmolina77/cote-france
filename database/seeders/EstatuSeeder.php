<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Estatu;

class EstatuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Estatu::create([
            'estatus_descripcion' =>'Seguimiento',
        ]);
        Estatu::create([
            'estatus_descripcion' =>'Ganado',
        ]);
        Estatu::create([
            'estatus_descripcion' =>'No le intersa',
        ]);
        Estatu::create([
            'estatus_descripcion' =>'Por contactar',
        ]);
        Estatu::create([
            'estatus_descripcion' =>'Cotización',
        ]);
        Estatu::create([
            'estatus_descripcion' =>'Negociación',
        ]);
        Estatu::create([
            'estatus_descripcion' =>'Mantener',
        ]);
        Estatu::create([
            'estatus_descripcion' =>'Venta Fallida',
        ]);
        Estatu::create([
            'estatus_descripcion' =>'No contesta',
        ]);
    }
}
