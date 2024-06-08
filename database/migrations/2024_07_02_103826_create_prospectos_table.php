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
        Schema::create('prospectos', function (Blueprint $table) {
            $table->id('prospectos_id');
            $table->string('prospectos_nombres',50);
            $table->string('prospectos_apellidos',50);
            $table->string('prospectos_telefono',80);
            $table->string('prospectos_correo',100);
            $table->unsignedBigInteger('origenes_id');
            $table->unsignedBigInteger('seguimientos_id');
            $table->unsignedBigInteger('estatus_id');
            $table->text('prospectos_comentarios');
            $table->date('prospectos_fecha');
            $table->foreign('seguimientos_id')->references('seguimientos_id')->on('seguimientos');
            $table->foreign('origenes_id')->references('origenes_id')->on('origenes');
            $table->foreign('estatus_id')->references('estatus_id')->on('estatus');
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
        Schema::dropIfExists('prospectos');
    }
};
