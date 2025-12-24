<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grupos_inactivos', function (Blueprint $table) {
            $table->id('grupo_inactivo_id');
            $table->unsignedBigInteger('grupo_id');
            $table->date('fecha');
            $table->unsignedBigInteger('horas_id');
            $table->timestamps();

            $table->foreign('grupo_id')->references('grupo_id')->on('grupos');
            $table->foreign('horas_id')->references('horas_id')->on('horas');
            $table->unique(['grupo_id', 'fecha', 'horas_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos_inactivos');
    }
};
