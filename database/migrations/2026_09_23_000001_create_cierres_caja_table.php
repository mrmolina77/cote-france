<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id('cierre_caja_id');
            $table->unsignedBigInteger('cajero_id');
            $table->date('fecha_operacion');
            $table->dateTime('ventana_inicio');
            $table->dateTime('ventana_fin');
            $table->char('zona_horaria', 64);
            $table->json('totales_esperados');
            $table->json('importes_contados');
            $table->json('diferencias');
            $table->json('snapshot_movimientos');
            $table->unsignedBigInteger('cerrado_por');
            $table->dateTime('cerrado_en');
            $table->text('observaciones')->nullable();
            $table->string('estado', 20)->default('cerrado');
            $table->timestamps();

            $table->foreign('cajero_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('cerrado_por')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['cajero_id', 'fecha_operacion'], 'cierres_caja_cajero_fecha_unique');
            $table->index(['fecha_operacion', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_caja');
    }
};
