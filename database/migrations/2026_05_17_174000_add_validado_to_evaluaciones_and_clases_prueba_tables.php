<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->boolean('validado')->default(false)->after('observacion');
        });

        Schema::table('clases_prueba', function (Blueprint $table) {
            $table->boolean('validado')->default(false)->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->dropColumn('validado');
        });

        Schema::table('clases_prueba', function (Blueprint $table) {
            $table->dropColumn('validado');
        });
    }
};
