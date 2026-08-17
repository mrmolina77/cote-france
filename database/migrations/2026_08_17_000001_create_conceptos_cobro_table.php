<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('conceptos_cobro', function (Blueprint $table) {
            $table->id('concepto_cobro_id');
            $table->string('clave', 50);
            $table->string('nombre', 120);
            $table->text('descripcion')->nullable();
            $table->string('clave_producto_servicio_sat', 20)->nullable();
            $table->string('clave_unidad_sat', 10)->nullable();
            $table->string('objeto_impuesto_sat', 10)->nullable();
            $table->decimal('tasa_iva', 8, 6)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique('clave');
            $table->index(['activo', 'orden']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('conceptos_cobro');
    }
};
