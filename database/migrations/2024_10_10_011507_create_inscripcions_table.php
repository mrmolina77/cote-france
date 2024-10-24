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
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id('inscripciones_id');
            $table->date('fecha_inscripcion');
            $table->unsignedBigInteger('prospectos_id');
            $table->unsignedBigInteger('cursos_id');
            $table->unsignedBigInteger('grupo_id');
            $table->foreign('prospectos_id')->references('prospectos_id')->on('prospectos');
            $table->foreign('cursos_id')->references('cursos_id')->on('cursos');
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
        Schema::dropIfExists('inscripcions');
    }
};
