<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // "contabilidad" exceeds the original 10-character limit. This widening preserves
        // every role ID, assignment and value; SQLite does not enforce VARCHAR lengths.
        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE roles MODIFY roles_codigo VARCHAR(32) NOT NULL'),
            'pgsql' => DB::statement('ALTER TABLE roles ALTER COLUMN roles_codigo TYPE VARCHAR(32)'),
            'sqlsrv' => DB::statement('ALTER TABLE roles ALTER COLUMN roles_codigo VARCHAR(32) NOT NULL'),
            default => null,
        };
    }

    public function down(): void
    {
        // Intentionally irreversible: shrinking could truncate an assigned stable role code.
    }
};
