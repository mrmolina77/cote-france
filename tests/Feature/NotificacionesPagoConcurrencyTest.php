<?php

namespace Tests\Feature;

use App\Services\Facturacion\GeneradorComprobantePagoService;
use App\Services\Facturacion\NotificacionPagoService;
use App\Models\AuditoriaPago;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class NotificacionesPagoConcurrencyTest extends ComprobantePagoTestCase
{
    public function test_unique_constraint_makes_repeated_initial_and_same_resend_action_idempotent(): void
    {
        Storage::fake('local'); Queue::fake();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $receipt = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());
        $service = app(NotificacionPagoService::class);
        $service->solicitarInicialRecibido($pago, $receipt); $service->solicitarInicialRecibido($pago, $receipt);
        $service->solicitarReenvio($pago, $receipt, $admin->getKey(), 'same-action');
        $service->solicitarReenvio($pago, $receipt, $admin->getKey(), 'same-action');
        $this->assertDatabaseCount('notificaciones_pago', 2);
        Queue::assertPushed(\App\Jobs\EnviarNotificacionPago::class, 2);
        $this->assertSame(1, AuditoriaPago::where('pago_id', $pago->getKey())
            ->where('accion', AuditoriaPago::ENVIAR_CORREO)->count());
        $this->assertSame(1, AuditoriaPago::where('pago_id', $pago->getKey())
            ->where('accion', AuditoriaPago::REENVIAR_CORREO)->count());
    }

    public function test_mysql_two_real_processes_compete_for_initial_and_same_resend_token(): void
    {
        foreach (['HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD'] as $variable) {
            if (getenv('EPIC13_MYSQL_'.$variable) === false) {
                $this->markTestSkipped('Configure EPIC13_MYSQL_*; la base debe ser exclusiva y contener "test" en su nombre.');
            }
        }
        $database = (string) getenv('EPIC13_MYSQL_DATABASE');
        $this->assertStringContainsString('test', strtolower($database), 'Se rehúsa limpiar una base que no esté identificada como de pruebas.');
        config()->set('database.default', 'epic13');
        config()->set('database.connections.epic13', $this->mysqlConfig());
        config()->set('queue.default', 'database');
        DB::purge('epic13');
        Artisan::call('migrate:fresh', ['--database' => 'epic13', '--force' => true]);
        (new \Database\Seeders\EstadoSeeder())->run();
        (new \Database\Seeders\ConceptoCobroSeeder())->run();
        (new \Database\Seeders\MetodoPagoSeeder())->run();
        (new \Database\Seeders\ModalidadSeeder())->run();
        $admin = $this->user('admin'); ['pago' => $pago] = $this->pagoConfirmado($admin);
        $pago->responsablePago->update(['correo' => 'payer@example.com']);
        $recibo = app(GeneradorComprobantePagoService::class)->generar($pago, $admin->getKey());

        $this->runWorkers($pago->getKey(), $recibo->getKey(), $admin->getKey(), 'initial');
        $this->assertSame(1, DB::table('notificaciones_pago')->where('tipo_solicitud', 'inicial')->count());
        $this->assertSame(1, DB::table('jobs')->count());
        $this->runWorkers($pago->getKey(), $recibo->getKey(), $admin->getKey(), 'resend');
        $this->assertSame(1, DB::table('notificaciones_pago')->where('tipo_solicitud', 'reenvio')->count());
        $this->assertSame(2, DB::table('jobs')->count());
        $this->assertSame(2, DB::table('notificaciones_pago')->distinct()->count('clave_idempotencia'));
        $this->assertSame(1, DB::table('auditoria_pagos')->where('accion', AuditoriaPago::ENVIAR_CORREO)->count());
        $this->assertSame(1, DB::table('auditoria_pagos')->where('accion', AuditoriaPago::REENVIAR_CORREO)->count());
        Artisan::call('migrate:reset', ['--database' => 'epic13', '--force' => true]);
    }

    private function mysqlConfig(): array
    {
        return ['driver' => 'mysql', 'host' => getenv('EPIC13_MYSQL_HOST'), 'port' => getenv('EPIC13_MYSQL_PORT'),
            'database' => getenv('EPIC13_MYSQL_DATABASE'), 'username' => getenv('EPIC13_MYSQL_USERNAME'),
            'password' => getenv('EPIC13_MYSQL_PASSWORD'), 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '', 'strict' => true, 'engine' => null];
    }

    private function runWorkers(int $pago, int $recibo, int $usuario, string $action): void
    {
        $dir = sys_get_temp_dir().'/epic13-'.bin2hex(random_bytes(6)); mkdir($dir, 0700, true);
        $script = $dir.'/worker.php';
        file_put_contents($script, <<<'PHP'
<?php
require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
while (!file_exists($argv[2])) usleep(1000);
$pago = App\Models\Pago::findOrFail((int)$argv[3]);
$recibo = App\Models\ComprobantePago::findOrFail((int)$argv[4]);
$service = app(App\Services\Facturacion\NotificacionPagoService::class);
$argv[6] === 'initial'
    ? $service->solicitarInicialRecibido($pago, $recibo)
    : $service->solicitarReenvio($pago, $recibo, (int)$argv[5], 'same-real-action');
PHP);
        $env = array_merge(array_filter($_SERVER, 'is_scalar'), array_filter($_ENV, 'is_scalar'), [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'QUEUE_CONNECTION' => 'database',
            'DB_HOST' => getenv('EPIC13_MYSQL_HOST'), 'DB_PORT' => getenv('EPIC13_MYSQL_PORT'),
            'DB_DATABASE' => getenv('EPIC13_MYSQL_DATABASE'), 'DB_USERNAME' => getenv('EPIC13_MYSQL_USERNAME'),
            'DB_PASSWORD' => getenv('EPIC13_MYSQL_PASSWORD'),
        ]);
        $processes = [];
        for ($i = 0; $i < 2; $i++) {
            $err = $dir.'/err-'.$i; $out = $dir.'/out-'.$i;
            $processes[] = [proc_open([PHP_BINARY, $script, base_path(), $dir.'/go', $pago, $recibo, $usuario, $action],
                [['pipe', 'r'], ['file', $out, 'a'], ['file', $err, 'a']], $pipes, base_path(), $env), $out, $err];
        }
        touch($dir.'/go');
        foreach ($processes as [$process, $out, $err]) {
            $code = proc_close($process);
            $this->assertSame(0, $code, 'Proceso concurrente falló. STDOUT: '.@file_get_contents($out).' STDERR: '.@file_get_contents($err));
        }
    }
}
