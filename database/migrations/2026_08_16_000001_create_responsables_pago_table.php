<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsables_pago', function (Blueprint $table) {
            $table->id('responsable_pago_id');
            $table->string('tipo', 20);
            $table->unsignedBigInteger('prospectos_id')->nullable();
            $table->string('nombre_razon_social');
            $table->string('telefono', 80)->nullable();
            $table->string('correo')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('prospectos_id')
                ->references('prospectos_id')
                ->on('prospectos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsables_pago');
    }
};
