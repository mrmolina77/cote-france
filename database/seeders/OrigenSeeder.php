<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Origen;

class OrigenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Origen::create([
            'origenes_descripcion' =>'Instagram',
        ]);
        Origen::create([
            'origenes_descripcion' =>'WhatsApp',
        ]);
        Origen::create([
            'origenes_descripcion' =>'Correo',
        ]);
        Origen::create([
            'origenes_descripcion' =>'Teléfono',
        ]);
        Origen::create([
            'origenes_descripcion' =>'Google Ads',
        ]);

    }
}
