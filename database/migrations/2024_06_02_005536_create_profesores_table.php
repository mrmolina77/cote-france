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
        Schema::create('profesores', function (Blueprint $table) {
            $table->id('profesores_id');
            $table->string('profesores_nombres',100);
            $table->string('profesores_apellidos',100);
            $table->string('profesores_email',250);
            $table->char('profesores_color',7)->nullable();
            $table->date('profesores_fecha_ingreso');
            $table->integer('profesores_horas_semanales')->nullable();
            $table->unsignedBigInteger('modalidad_id');
            $table->foreign('modalidad_id')->references('modalidad_id')->on('modalidades');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profesors');
    }
};
