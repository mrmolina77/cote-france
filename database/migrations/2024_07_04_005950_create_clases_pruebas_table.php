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
        Schema::create('clases_pruebas', function (Blueprint $table) {
            $table->id('clasespruebas_id');
            $table->date('clasespruebas_fecha');
            $table->time('clasespruebas_hora_inicio');
            $table->time('clasespruebas_hora_fin');
            $table->unsignedBigInteger('profesores_id');
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
        Schema::dropIfExists('clase_pruebas');
    }
};
