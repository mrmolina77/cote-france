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
        Schema::create('grupos_detalles', function (Blueprint $table) {
            $table->id('detalles_id');
            $table->unsignedBigInteger('grupo_id')->nullable();
            $table->unsignedBigInteger('horas_id')->nullable();
            $table->unsignedBigInteger('espacios_id')->nullable();
            $table->unsignedBigInteger('dias_id')->nullable();
            $table->foreign('grupo_id')->references('grupo_id')->on('grupos');
            $table->foreign('horas_id')->references('horas_id')->on('horas');
            $table->foreign('espacios_id')->references('espacios_id')->on('espacios');
            $table->foreign('dias_id')->references('dias_id')->on('dias');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grupos_detalles');
    }
};
