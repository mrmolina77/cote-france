<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id('roles_id');
            $table->string('roles_codigo',10);
            $table->string('roles_nombre',100);
            $table->timestamps();
            $table->softDeletes();
        });

        \Illuminate\Support\Facades\DB::table('roles')->insert([
            ['roles_id' => 1, 'roles_codigo' => 'admin', 'roles_nombre' => 'Administradores'],
            ['roles_id' => 2, 'roles_codigo' => 'venta', 'roles_nombre' => 'Ventas'],
            ['roles_id' => 3, 'roles_codigo' => 'profe', 'roles_nombre' => 'Profesores'],
            ['roles_id' => 4, 'roles_codigo' => 'alum', 'roles_nombre' => 'Alumnos'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
};
