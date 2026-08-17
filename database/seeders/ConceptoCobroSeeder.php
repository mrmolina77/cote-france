<?php

namespace Database\Seeders;

use App\Models\ConceptoCobro;
use Illuminate\Database\Seeder;

class ConceptoCobroSeeder extends Seeder
{
    public function run()
    {
        $conceptos = [
            ['clave' => 'INSCRIPCION', 'nombre' => 'Inscripción', 'descripcion' => null, 'orden' => 10],
            ['clave' => 'MENSUALIDAD', 'nombre' => 'Mensualidad', 'descripcion' => null, 'orden' => 20],
            ['clave' => 'MATERIAL', 'nombre' => 'Material', 'descripcion' => null, 'orden' => 30],
            ['clave' => 'EXAMEN', 'nombre' => 'Examen', 'descripcion' => null, 'orden' => 40],
            ['clave' => 'CURSO_ESPECIAL', 'nombre' => 'Curso especial', 'descripcion' => null, 'orden' => 50],
            ['clave' => 'CLASE_INDIVIDUAL', 'nombre' => 'Clase individual', 'descripcion' => null, 'orden' => 60],
            ['clave' => 'RECARGO', 'nombre' => 'Recargo', 'descripcion' => null, 'orden' => 70],
            ['clave' => 'DESCUENTO', 'nombre' => 'Descuento', 'descripcion' => null, 'orden' => 80],
            ['clave' => 'OTRO', 'nombre' => 'Otro', 'descripcion' => null, 'orden' => 90],
        ];

        foreach ($conceptos as $concepto) {
            ConceptoCobro::firstOrCreate(
                ['clave' => $concepto['clave']],
                [
                    'nombre' => $concepto['nombre'],
                    'descripcion' => $concepto['descripcion'],
                    'orden' => $concepto['orden'],
                    'activo' => true,
                ]
            );
        }
    }
}
