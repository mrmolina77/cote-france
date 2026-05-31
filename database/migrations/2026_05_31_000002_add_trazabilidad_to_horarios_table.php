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
        Schema::table('horarios', function (Blueprint $table) {
            $table->string('origen')->default('programado')->after('profesores_id');
            $table->boolean('protegido')->default(false)->after('origen');
            $table->timestamp('protegido_at')->nullable()->after('protegido');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->dropColumn(['origen', 'protegido', 'protegido_at']);
        });
    }
};
