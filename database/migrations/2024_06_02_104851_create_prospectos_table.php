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
            $table->string('prospectos_apellidos',50)->nullable();
            $table->string('prospectos_telefono1',80);
            $table->string('prospectos_telefono2',80)->nullable();
            $table->string('prospectos_correo',100)->nullable();
            $table->unsignedBigInteger('origenes_id')->nullable();
            $table->unsignedBigInteger('seguimientos_id')->nullable();
            $table->unsignedBigInteger('estatus_id')->nullable();
            $table->unsignedBigInteger('modalidad_id')->nullable();
            $table->unsignedBigInteger('cursos_id')->nullable();
            $table->text('prospectos_comentarios')->nullable();
            $table->date('prospectos_fecha')->nullable();
            $table->unsignedBigInteger('grupo_id')->nullable();
            $table->unsignedBigInteger('horarios_id')->nullable();
            $table->foreign('seguimientos_id')->references('seguimientos_id')->on('seguimientos');
            $table->foreign('origenes_id')->references('origenes_id')->on('origenes');
            $table->foreign('estatus_id')->references('estatus_id')->on('estatus');
            $table->foreign('grupo_id')->references('grupo_id')->on('grupos');
            $table->foreign('horarios_id')->references('horarios_id')->on('horarios');
            $table->foreign('modalidad_id')->references('modalidad_id')->on('modalidades');
            $table->foreign('cursos_id')->references('cursos_id')->on('cursos');
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
