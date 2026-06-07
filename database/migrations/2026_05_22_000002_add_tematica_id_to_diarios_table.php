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
        Schema::table('diarios', function (Blueprint $table) {
            $table->unsignedBigInteger('tematica_id')->nullable()->after('capitulos_id');
            $table->foreign('tematica_id')
                ->references('tematica_id')
                ->on('tematicas')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diarios', function (Blueprint $table) {
            $table->dropForeign(['tematica_id']);
            $table->dropColumn('tematica_id');
        });
    }
};
