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
        Schema::create('bloqueos_profesores', function (Blueprint $table) {
            $table->id('bloqueo_id');
            $table->unsignedBigInteger('profesor_id');
            $table->unsignedBigInteger('dias_id')->nullable();    // Día de la semana
            $table->unsignedBigInteger('horas_id')->nullable();   // Hora específica
            $table->date('fecha')->nullable();                    // Fecha puntual (feriados o permisos)
            $table->string('motivo')->nullable();
            $table->timestamps();

            $table->foreign('profesor_id')->references('profesores_id')->on('profesores');
            $table->foreign('dias_id')->references('dias_id')->on('dias');
            $table->foreign('horas_id')->references('horas_id')->on('horas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bloqueos_profesores');
    }
};
