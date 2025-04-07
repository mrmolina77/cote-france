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
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id('asistencias_id');
            $table->unsignedBigInteger('prospectos_id');
            $table->tinyInteger('asistencias')->default(2)->comment('0 = No asistio, 1 = Asistio, 2 = En espera');
            $table->date('asistencias_fecha');
            $table->foreign('prospectos_id')->references('prospectos_id')->on('prospectos');
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
        Schema::dropIfExists('asistencias');
    }
};
