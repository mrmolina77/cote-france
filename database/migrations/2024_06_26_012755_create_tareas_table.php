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
        Schema::create('tareas', function (Blueprint $table) {
            $table->id('tareas_id');
            $table->string('tareas_descripcion',100);
            $table->date('tareas_fecha');
            $table->longText('tareas_comentario');
            $table->unsignedBigInteger('prospectos_id');
            $table->foreign('prospectos_id')->references('prospectos_id')->on('prospectos');
            $table->unsignedBigInteger('est_tareas_id');
            $table->foreign('est_tareas_id')->references('est_tareas_id')->on('estatu_tareas');
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
        Schema::dropIfExists('tareas');
    }
};
