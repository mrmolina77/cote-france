<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diarios', function (Blueprint $table) {
            $table->boolean('validado_datos_generales')->default(false)->after('tematica_id');
            $table->boolean('validado_contenido_clase')->default(false)->after('validado_datos_generales');
            $table->boolean('validado_estudiantes')->default(false)->after('validado_contenido_clase');
            $table->boolean('validado_prospectos')->default(false)->after('validado_estudiantes');
        });
    }

    public function down(): void
    {
        Schema::table('diarios', function (Blueprint $table) {
            $table->dropColumn([
                'validado_datos_generales',
                'validado_contenido_clase',
                'validado_estudiantes',
                'validado_prospectos',
            ]);
        });
    }
};
