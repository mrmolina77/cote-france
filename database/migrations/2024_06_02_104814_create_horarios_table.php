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
        Schema::create('horarios', function (Blueprint $table) {
            $table->id('horarios_id');
            $table->date('horarios_dia');
            $table->unsignedBigInteger('espacios_id');
            $table->unsignedBigInteger('horas_id');
            $table->unsignedBigInteger('grupo_id');
            $table->unsignedBigInteger('profesores_id');
            $table->foreign('espacios_id')->references('espacios_id')->on('espacios');
            $table->foreign('horas_id')->references('horas_id')->on('horas');
            $table->foreign('grupo_id')->references('grupo_id')->on('grupos');
            $table->foreign('profesores_id')->references('profesores_id')->on('profesores');
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
        Schema::dropIfExists('horarios');
    }
};
