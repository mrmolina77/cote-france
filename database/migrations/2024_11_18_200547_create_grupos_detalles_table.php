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
            $table->unsignedBigInteger('profesores_id')->nullable();
            $table->unsignedBigInteger('dias_id')->nullable();
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
