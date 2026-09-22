<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_pago', function (Blueprint $table) {
            $table->id('notificacion_pago_id');
            $table->unsignedBigInteger('pago_id');
            $table->unsignedBigInteger('comprobante_pago_id')->nullable();
            $table->string('tipo', 30);
            $table->string('tipo_solicitud', 20);
            $table->string('clave_idempotencia', 100)->unique();
            $table->string('destinatario', 254);
            $table->string('estado', 20)->default('pendiente');
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->dateTime('programado_en');
            $table->dateTime('iniciado_en')->nullable();
            $table->dateTime('enviado_en')->nullable();
            $table->dateTime('ultimo_intento_en')->nullable();
            $table->string('ultimo_error', 500)->nullable();
            $table->unsignedBigInteger('solicitado_por')->nullable();
            $table->dateTime('solicitado_en')->nullable();
            $table->string('mensaje_proveedor_id', 255)->nullable();
            $table->timestamps();

            $table->foreign('pago_id')->references('pago_id')->on('pagos')->restrictOnDelete();
            $table->foreign('comprobante_pago_id')->references('comprobante_pago_id')->on('comprobantes_pago')->restrictOnDelete();
            $table->foreign('solicitado_por')->references('id')->on('users')->nullOnDelete();
            $table->index(['pago_id', 'tipo', 'tipo_solicitud']);
            $table->index(['estado', 'programado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_pago');
    }
};
