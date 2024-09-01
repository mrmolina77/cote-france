<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Role::create([
            'roles_codigo' =>'admin',
            'roles_nombre' =>'Administradores',
        ]);
        Role::create([
            'roles_codigo' =>'venta',
            'roles_nombre' =>'Ventas',
        ]);
        Role::create([
            'roles_codigo' =>'profe',
            'roles_nombre' =>'Profesores',
        ]);
        Role::create([
            'roles_codigo' =>'alum',
            'roles_nombre' =>'Alumnos',
        ]);
    }
}
