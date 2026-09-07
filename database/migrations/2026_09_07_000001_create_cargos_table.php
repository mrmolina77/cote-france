<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargos', function (Blueprint $table) {
            $table->id('cargo_id');
            $table->unsignedBigInteger('inscripciones_id');
            $table->unsignedBigInteger('concepto_cobro_id');
            $table->unsignedSmallInteger('periodo_anio')->nullable();
            $table->unsignedTinyInteger('periodo_mes')->nullable();
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->char('moneda', 3)->default('MXN');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('recargo', 12, 2)->default(0);
            $table->decimal('impuestos', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('saldo_pendiente', 12, 2);
            $table->string('estado', 20)->default('pendiente');
            $table->string('origen', 20)->default('manual');
            $table->string('clave_idempotencia', 191)->nullable()->unique();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('inscripciones_id')
                ->references('inscripciones_id')->on('inscripciones')->restrictOnDelete();
            $table->foreign('concepto_cobro_id')
                ->references('concepto_cobro_id')->on('conceptos_cobro')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['inscripciones_id', 'estado']);
            $table->index(['fecha_vencimiento', 'estado']);
            $table->index('concepto_cobro_id');
            $table->index(['periodo_anio', 'periodo_mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
