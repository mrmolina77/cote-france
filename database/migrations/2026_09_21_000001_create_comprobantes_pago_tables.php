<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consecutivos_comprobante_pago', function (Blueprint $table) {
            $table->unsignedSmallInteger('anio')->primary();
            $table->unsignedBigInteger('ultimo_consecutivo')->default(0);
            $table->timestamps();
        });

        Schema::create('comprobantes_pago', function (Blueprint $table) {
            $table->id('comprobante_pago_id');
            $table->unsignedBigInteger('pago_id')->unique();
            $table->string('folio', 19)->unique();
            $table->unsignedSmallInteger('anio_folio');
            $table->unsignedBigInteger('secuencia_folio');
            $table->string('disco', 40);
            $table->string('ruta_pdf', 500);
            $table->char('hash_sha256', 64);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano_bytes');
            $table->dateTime('generado_en');
            $table->unsignedBigInteger('generado_por')->nullable();
            $table->timestamps();

            $table->foreign('pago_id')->references('pago_id')->on('pagos')->restrictOnDelete();
            $table->foreign('generado_por')->references('id')->on('users')->nullOnDelete();
            $table->unique(['anio_folio', 'secuencia_folio']);
            $table->unique(['disco', 'ruta_pdf']);
            $table->index('generado_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes_pago');
        Schema::dropIfExists('consecutivos_comprobante_pago');
    }
};
