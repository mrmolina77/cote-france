<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profesores', function (Blueprint $table) {
            $table->boolean('profesores_activo')->default(true)->after('profesores_horas_semanales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profesores', function (Blueprint $table) {
            $table->dropColumn('profesores_activo');
        });
    }
};
