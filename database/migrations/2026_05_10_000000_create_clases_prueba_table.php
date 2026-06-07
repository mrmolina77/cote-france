<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clases_prueba', function (Blueprint $table) {
            $table->id('clase_prueba_id');
            $table->unsignedBigInteger('prospectos_id');
            $table->unsignedBigInteger('grupo_id');
            $table->unsignedBigInteger('horarios_id')->nullable();
            $table->date('horarios_dia');
            $table->unsignedBigInteger('horas_id');
            $table->unsignedBigInteger('profesores_id')->nullable();
            $table->unsignedBigInteger('espacios_id')->nullable();
            $table->unsignedBigInteger('modalidad_id')->nullable();
            $table->tinyInteger('asistio')->nullable();
            $table->string('observacion', 255)->nullable();
            $table->string('estado', 30)->default('programada');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('prospectos_id')->references('prospectos_id')->on('prospectos');
            $table->foreign('grupo_id')->references('grupo_id')->on('grupos');
            $table->foreign('horarios_id')->references('horarios_id')->on('horarios')->nullOnDelete();
            $table->foreign('horas_id')->references('horas_id')->on('horas');
            $table->foreign('profesores_id')->references('profesores_id')->on('profesores')->nullOnDelete();
            $table->foreign('espacios_id')->references('espacios_id')->on('espacios')->nullOnDelete();
            $table->foreign('modalidad_id')->references('modalidad_id')->on('modalidades')->nullOnDelete();

            $table->index('prospectos_id');
            $table->index('grupo_id');
            $table->index('horarios_id');
            $table->index('horarios_dia');
            $table->index('horas_id');
            $table->index('estado');
            $table->index(['grupo_id', 'horarios_dia', 'horas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clases_prueba');
    }
};
