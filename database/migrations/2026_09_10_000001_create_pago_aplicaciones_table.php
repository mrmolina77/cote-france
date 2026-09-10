<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_aplicaciones', function (Blueprint $table) {
            $table->id('pago_aplicacion_id');
            $table->unsignedBigInteger('pago_id');
            $table->unsignedBigInteger('cargo_id');
            $table->decimal('importe_aplicado', 12, 2);
            $table->decimal('saldo_anterior', 12, 2);
            $table->decimal('saldo_posterior', 12, 2);
            $table->timestamps();

            $table->foreign('pago_id')->references('pago_id')->on('pagos')->restrictOnDelete();
            $table->foreign('cargo_id')->references('cargo_id')->on('cargos')->restrictOnDelete();
            $table->index('cargo_id');
            $table->unique(['pago_id', 'cargo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_aplicaciones');
    }
};
