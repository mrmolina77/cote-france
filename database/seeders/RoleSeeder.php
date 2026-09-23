<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ([
            'admin' => 'Administradores',
            'venta' => 'Ventas',
            'profe' => 'Profesores',
            'alum' => 'Alumnos',
            'caja' => 'Caja / Cobranza',
            'contabilidad' => 'Contabilidad',
        ] as $codigo => $nombre) {
            // The code is the stable natural key. Existing rows, IDs and names are never changed.
            Role::query()->firstOrCreate(['roles_codigo' => $codigo], ['roles_nombre' => $nombre]);
        }
    }
}
