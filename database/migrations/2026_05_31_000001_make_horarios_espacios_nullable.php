<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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
        if (! Schema::hasTable('horarios') || ! Schema::hasColumn('horarios', 'espacios_id')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE horarios MODIFY espacios_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE horarios ALTER COLUMN espacios_id DROP NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('horarios') || ! Schema::hasColumn('horarios', 'espacios_id')) {
            return;
        }

        $driver = DB::getDriverName();
        $fallbackEspacioId = DB::table('espacios')->min('espacios_id');

        if (! $fallbackEspacioId) {
            return;
        }

        DB::table('horarios')->whereNull('espacios_id')->update(['espacios_id' => $fallbackEspacioId]);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE horarios MODIFY espacios_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE horarios ALTER COLUMN espacios_id SET NOT NULL');
        }
    }
};
