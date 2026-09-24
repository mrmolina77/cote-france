<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierre_caja_movimientos', function (Blueprint $table) {
            $table->id('cierre_caja_movimiento_id');
            $table->unsignedBigInteger('cierre_caja_id');
            $table->unsignedInteger('secuencia');
            $table->string('tipo', 24);
            $table->unsignedBigInteger('pago_id')->nullable();
            $table->string('folio')->nullable();
            $table->dateTime('fecha')->nullable();
            $table->unsignedBigInteger('metodo_pago_id')->nullable();
            $table->string('metodo');
            $table->decimal('importe', 14, 2);
            $table->char('moneda', 3);
            $table->string('registrado_por')->nullable();
            $table->string('actor')->nullable();
            $table->foreign('cierre_caja_id')->references('cierre_caja_id')->on('cierres_caja')->cascadeOnDelete();
            $table->unique(['cierre_caja_id', 'secuencia'], 'cierre_movimientos_secuencia_unique');
            $table->index(['cierre_caja_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierre_caja_movimientos');
    }
};
