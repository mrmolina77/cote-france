<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JobsMigrationSafetyTest extends TestCase
{
    private string $database;

    protected function setUp(): void
    {
        parent::setUp();
        $this->database = tempnam(sys_get_temp_dir(), 'jobs-migration-');
        config()->set('database.default', 'jobs_safety');
        config()->set('database.connections.jobs_safety', ['driver' => 'sqlite', 'database' => $this->database, 'prefix' => '']);
        DB::purge('jobs_safety');
    }

    protected function tearDown(): void
    {
        DB::purge('jobs_safety');
        @unlink($this->database);
        parent::tearDown();
    }

    public function test_clean_database_creates_laravel_database_queue_schema_and_conservative_down_preserves_it(): void
    {
        $migration = require database_path('migrations/2026_09_22_000002_create_jobs_table.php');
        $migration->up();
        foreach (['id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('jobs', $column));
        }
        $migration->down();
        $this->assertTrue(Schema::hasTable('jobs'), 'El rollback conservador nunca elimina trabajos cuya procedencia ya no puede probarse.');
    }

    public function test_existing_table_and_pending_job_survive_up_and_down(): void
    {
        Schema::create('jobs', function (Blueprint $table): void {
            $table->bigIncrements('id'); $table->string('queue'); $table->longText('payload');
            $table->unsignedTinyInteger('attempts'); $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at'); $table->unsignedInteger('created_at');
        });
        DB::table('jobs')->insert(['queue' => 'payments', 'payload' => '{"pending":true}', 'attempts' => 0, 'available_at' => 1, 'created_at' => 1]);
        $migration = require database_path('migrations/2026_09_22_000002_create_jobs_table.php');
        $migration->up(); $migration->down();
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertSame('{"pending":true}', DB::table('jobs')->value('payload'));
    }
}
