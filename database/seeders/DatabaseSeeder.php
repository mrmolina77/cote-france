<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(EstatuSeeder::class);
        $this->call(OrigenSeeder::class);
        $this->call(NivelSeeder::class);
        $this->call(CapituloSeeder::class);
        $this->call(OrigenSeeder::class);
        $this->call(SeguimientoSeeder::class);
        $this->call(EstatuTareaSeeder::class);
        $this->call(CursoSeeder::class);
        $this->call(ModalidadSeeder::class);
        $this->call(ProfesorSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(EstadoSeeder::class);
        $this->call(EspacioSeeder::class);
        $this->call(HoraSeeder::class);
        $this->call(DiaSeeder::class);
    }
}
