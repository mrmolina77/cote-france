<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('grupos_inactivos', function (Blueprint $table) {
            $table->unsignedBigInteger('modalidad_id')->nullable()->default(2)->after('horas_id');
            $table->foreign('modalidad_id')->references('modalidad_id')->on('modalidades');
            $table->unique(['grupo_id', 'fecha', 'horas_id', 'modalidad_id']);
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('UPDATE grupos_inactivos SET modalidad_id = (SELECT g.modalidad_id FROM grupos g WHERE g.grupo_id = grupos_inactivos.grupo_id) WHERE EXISTS (SELECT 1 FROM grupos g WHERE g.grupo_id = grupos_inactivos.grupo_id)');
        } else {
            DB::table('grupos_inactivos')
                ->join('grupos', 'grupos_inactivos.grupo_id', '=', 'grupos.grupo_id')
                ->update(['grupos_inactivos.modalidad_id' => DB::raw('grupos.modalidad_id')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grupos_inactivos', function (Blueprint $table) {
            $table->dropForeign(['modalidad_id']);
            $table->dropUnique('grupos_inactivos_grupo_id_fecha_horas_id_modalidad_id_unique');
            $table->dropColumn('modalidad_id');
        });
    }
};
