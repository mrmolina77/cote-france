<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        User::create([
            'name' =>'Administrador',
            'email' =>'admin@admin.com',
            'password' =>Hash::make('12345678'),
            'roles_id' =>1,
        ]);
        User::create([
            'name' =>'Ventas',
            'email' =>'ventas@admin.com',
            'password' =>Hash::make('12345678'),
            'roles_id' =>2,
        ]);
        User::create([
            'name' =>'Profesor',
            'email' =>'profesor@admin.com',
            'password' =>Hash::make('12345678'),
            'roles_id' =>3,
        ]);
        User::create([
            'name' =>'Alumno',
            'email' =>'alumno@admin.com',
            'password' =>Hash::make('12345678'),
            'roles_id' =>4,
        ]);
    }
}
