<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_pagos', function (Blueprint $table) {
            $table->id('auditoria_pago_id');
            $table->unsignedBigInteger('pago_id');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('accion', 30);
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->dateTime('ocurrido_en')->index();
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->json('metadatos')->nullable();
            $table->timestamps();

            $table->foreign('pago_id')->references('pago_id')->on('pagos')->restrictOnDelete();
            $table->foreign('usuario_id')->references('id')->on('users')->nullOnDelete();
            $table->index('pago_id');
            $table->index('usuario_id');
            $table->index('accion');
            $table->index(['pago_id', 'ocurrido_en']);
            $table->index(['accion', 'ocurrido_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_pagos');
    }
};
