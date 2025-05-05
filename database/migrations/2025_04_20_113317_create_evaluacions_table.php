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
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id('evaluacion_id');
            $table->unsignedBigInteger('prospectos_id');
            $table->unsignedBigInteger('horarios_id');
            $table->boolean('asistio')->nullable();
            $table->string('observacion',255)->nullable();
            $table->foreign('prospectos_id')->references('prospectos_id')->on('prospectos');
            $table->foreign('horarios_id')->references('horarios_id')->on('horarios');
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
        Schema::dropIfExists('evaluacions');
    }
};
