<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_perfiles_fiscales', function (Blueprint $table) {
            $table->id('auditoria_perfil_fiscal_id');
            $table->unsignedBigInteger('perfil_fiscal_id');
            $table->unsignedBigInteger('prospectos_id');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('accion', 30);
            $table->json('campos_modificados');
            $table->dateTime('ocurrido_en');
            $table->timestamps();
            $table->foreign('perfil_fiscal_id')->references('perfil_fiscal_id')->on('perfiles_fiscales')->restrictOnDelete();
            $table->foreign('prospectos_id')->references('prospectos_id')->on('prospectos')->restrictOnDelete();
            $table->foreign('usuario_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['perfil_fiscal_id', 'ocurrido_en'], 'auditoria_perfil_fecha_idx');
            $table->index(['prospectos_id', 'ocurrido_en'], 'auditoria_alumno_fecha_idx');
            $table->index(['accion', 'ocurrido_en'], 'auditoria_perfil_accion_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_perfiles_fiscales');
    }
};
