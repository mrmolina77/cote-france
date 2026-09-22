<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GeneradorFolioComprobanteConcurrencyTest extends TestCase
{
    public function test_mysql_asigna_folios_consecutivos_desde_procesos_independientes(): void
    {
        foreach (['HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD'] as $variable) {
            if (getenv('EPIC12_MYSQL_'.$variable) === false) {
                $this->markTestSkipped('Integración MySQL: configure EPIC12_MYSQL_* según docs/pruebas-concurrencia-comprobantes.md.');
            }
        }

        config()->set('database.default', 'epic12');
        config()->set('database.connections.epic12', [
            'driver' => 'mysql', 'host' => getenv('EPIC12_MYSQL_HOST'), 'port' => getenv('EPIC12_MYSQL_PORT'),
            'database' => getenv('EPIC12_MYSQL_DATABASE'), 'username' => getenv('EPIC12_MYSQL_USERNAME'),
            'password' => getenv('EPIC12_MYSQL_PASSWORD'), 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '', 'strict' => true, 'engine' => null,
        ]);
        DB::purge('epic12');
        Schema::dropIfExists('consecutivos_comprobante_pago');
        Schema::create('consecutivos_comprobante_pago', function (Blueprint $table): void {
            $table->unsignedSmallInteger('anio')->primary();
            $table->unsignedBigInteger('ultimo_consecutivo')->default(0);
            $table->timestamps();
        });

        $directorio = sys_get_temp_dir().'/epic12-concurrency-'.bin2hex(random_bytes(6));
        mkdir($directorio, 0700, true);
        $script = $directorio.'/worker.php';
        file_put_contents($script, <<<'PHP'
<?php
require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
while (! file_exists($argv[2])) usleep(1000);
$folio = app(App\Services\Facturacion\GeneradorFolioComprobanteService::class)
    ->generar(Carbon\CarbonImmutable::parse($argv[4]));
file_put_contents($argv[3], json_encode($folio, JSON_THROW_ON_ERROR));
PHP);
        $procesos = [];
        $entorno = array_merge(
            array_filter($_SERVER, 'is_scalar'),
            array_filter($_ENV, 'is_scalar'),
            [
                'APP_ENV' => 'testing',
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => getenv('EPIC12_MYSQL_HOST'),
                'DB_PORT' => getenv('EPIC12_MYSQL_PORT'),
                'DB_DATABASE' => getenv('EPIC12_MYSQL_DATABASE'),
                'DB_USERNAME' => getenv('EPIC12_MYSQL_USERNAME'),
                'DB_PASSWORD' => getenv('EPIC12_MYSQL_PASSWORD'),
            ]
        );
        for ($i = 0; $i < 4; $i++) {
            $salida = $directorio.'/resultado-'.$i.'.json';
            $errFile = $directorio.'/err-'.$i;
            $outFile = $directorio.'/out-'.$i;
            $comando = [PHP_BINARY, $script, base_path(), $directorio.'/iniciar', $salida, '2026-09-21'];
            $procesos[] = [
                'process' => proc_open($comando, [['pipe', 'r'], ['file', $outFile, 'a'], ['file', $errFile, 'a']], $pipes, base_path(), $entorno),
                'output' => $salida,
                'err' => $errFile,
                'out' => $outFile,
            ];
        }
        touch($directorio.'/iniciar');
        $folios = [];
        foreach ($procesos as $proceso) {
            $code = proc_close($proceso['process']);
            $errContent = file_exists($proceso['err']) ? file_get_contents($proceso['err']) : '';
            $outContent = file_exists($proceso['out']) ? file_get_contents($proceso['out']) : '';
            $this->assertSame(0, $code, "Falló un proceso independiente. STDOUT: {$outContent} STDERR: {$errContent}");
            $this->assertFileExists($proceso['output'], "El archivo de salida no existe. STDOUT: {$outContent} STDERR: {$errContent}");
            $folios[] = json_decode(file_get_contents($proceso['output']), true, 512, JSON_THROW_ON_ERROR);
        }
        sort($folios);
        $this->assertSame([1, 2, 3, 4], collect($folios)->pluck('secuencia')->sort()->values()->all());
        $this->assertCount(4, array_unique(array_column($folios, 'folio')));
        $this->assertSame(4, (int) DB::table('consecutivos_comprobante_pago')->where('anio', 2026)->value('ultimo_consecutivo'));
        $otroAnio = app(\App\Services\Facturacion\GeneradorFolioComprobanteService::class)->generar(\Carbon\CarbonImmutable::parse('2027-01-01'));
        $this->assertSame('REC-2027-000001', $otroAnio['folio']);
    }
}
