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
            $table->unsignedBigInteger('espacios_id');
            $table->tinyInteger('numero_semana');
            $table->unsignedBigInteger('dias_id');
            $table->unsignedBigInteger('horas_id');
            $table->unsignedBigInteger('grupo_id')->nullable();
            $table->foreign('espacios_id')->references('espacios_id')->on('espacios');
            $table->foreign('dias_id')->references('dias_id')->on('dias');
            $table->foreign('horas_id')->references('horas_id')->on('horas');
            $table->foreign('grupo_id')->references('grupo_id')->on('grupos');
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
