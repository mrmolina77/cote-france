<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id('metodo_pago_id');
            $table->string('clave', 50);
            $table->string('nombre', 120);
            $table->text('descripcion')->nullable();
            $table->string('clave_forma_pago_sat', 2)->nullable();
            $table->boolean('requiere_forma_pago_sat')->default(false);
            $table->boolean('requiere_banco')->default(false);
            $table->boolean('requiere_referencia')->default(false);
            $table->boolean('requiere_numero_cheque')->default(false);
            $table->boolean('requiere_rastreo_spei')->default(false);
            $table->boolean('requiere_autorizacion')->default(false);
            $table->boolean('requiere_terminal')->default(false);
            $table->boolean('requiere_ultimos_4_digitos')->default(false);
            $table->boolean('requiere_proveedor')->default(false);
            $table->boolean('requiere_anticipo_relacionado')->default(false);
            $table->boolean('requiere_comprobante')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique('clave');
            $table->index(['activo', 'orden']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('metodos_pago');
    }
};
