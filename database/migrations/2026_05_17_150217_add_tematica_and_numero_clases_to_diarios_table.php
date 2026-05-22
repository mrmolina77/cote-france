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
        Schema::table('diarios', function (Blueprint $table) {
            $table->string('tematica')->nullable();
            $table->decimal('numero_clases', 4, 1)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('diarios', function (Blueprint $table) {
            $table->dropColumn(['tematica', 'numero_clases']);
        });
    }
};
