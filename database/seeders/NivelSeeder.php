<?php

namespace Database\Seeders;

use App\Models\Nivel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NivelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Nivel::create([
            'nivel_descripcion' => 'basico',
        ]);
        Nivel::create([
            'nivel_descripcion' => 'intermedio',
        ]);
        Nivel::create([
            'nivel_descripcion' => 'avanzado',
        ]);
        Nivel::create([
            'nivel_descripcion' => 'experto',
        ]);
    }
}
