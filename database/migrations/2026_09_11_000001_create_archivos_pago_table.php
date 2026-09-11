<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos_pago', function (Blueprint $table) {
            $table->id('archivo_pago_id');
            $table->unsignedBigInteger('pago_id');
            $table->string('nombre_original');
            $table->string('disco', 40);
            $table->string('ruta', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano_bytes');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('pago_id')->references('pago_id')->on('pagos')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['pago_id', 'created_at']);
            $table->unique(['disco', 'ruta']);
        });
    }

    public function down(): void
    {
        // Los bytes se conservan: un rollback sólo retira la estructura de metadatos.
        Schema::dropIfExists('archivos_pago');
    }
};
