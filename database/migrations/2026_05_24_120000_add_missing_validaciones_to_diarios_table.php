<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('diarios')) {
            return;
        }

        Schema::table('diarios', function (Blueprint $table) {
            if (! Schema::hasColumn('diarios', 'validado_datos_generales')) {
                $table->boolean('validado_datos_generales')->default(false);
            }

            if (! Schema::hasColumn('diarios', 'validado_contenido_clase')) {
                $table->boolean('validado_contenido_clase')->default(false);
            }

            if (! Schema::hasColumn('diarios', 'validado_estudiantes')) {
                $table->boolean('validado_estudiantes')->default(false);
            }

            if (! Schema::hasColumn('diarios', 'validado_prospectos')) {
                $table->boolean('validado_prospectos')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('diarios')) {
            return;
        }

        Schema::table('diarios', function (Blueprint $table) {
            $columns = [
                'validado_datos_generales',
                'validado_contenido_clase',
                'validado_estudiantes',
                'validado_prospectos',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('diarios', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
