<?php

namespace Tests\Feature;

use App\Models\PerfilFiscal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerfilFiscalConcurrencyTest extends TestCase
{
    public function test_mysql_two_processes_leave_exactly_one_default_profile_for_student(): void
    {
        foreach (['HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD'] as $variable) {
            if (getenv('EPIC17_MYSQL_'.$variable) === false) {
                $this->markTestSkipped('Configure EPIC17_MYSQL_* con una base aislada cuyo nombre contenga "test".');
            }
        }
        $database = (string) getenv('EPIC17_MYSQL_DATABASE');
        if (! str_contains(strtolower($database), 'test')) $this->fail('EPIC17_MYSQL_DATABASE debe ser una base aislada con "test" en el nombre.');
        config()->set('database.default', 'epic17');
        config()->set('database.connections.epic17', $this->mysqlConfig());
        DB::purge('epic17');

        foreach (['auditoria_perfiles_fiscales', 'perfiles_fiscales', 'prospectos', 'users'] as $table) Schema::dropIfExists($table);
        Schema::create('users', fn (Blueprint $t) => $t->id());
        Schema::create('prospectos', fn (Blueprint $t) => $t->id('prospectos_id'));
        // Recreate only the profile portion because this isolated schema has no payment subsystem.
        Schema::create('perfiles_fiscales', function (Blueprint $t) {
            $t->id('perfil_fiscal_id'); $t->unsignedBigInteger('prospectos_id'); $t->string('tipo_persona', 10); $t->string('rfc', 13);
            $t->string('nombre_razon_social'); $t->char('codigo_postal_fiscal', 5); $t->string('regimen_fiscal', 10); $t->string('uso_cfdi', 10);
            $t->string('correo_facturacion', 254); $t->string('relacion_alumno', 120); $t->char('curp', 18)->nullable();
            $t->string('nivel_educativo', 120); $t->string('rvoe', 120); $t->boolean('predeterminado')->default(false);
            $t->boolean('activo')->default(true); $t->date('fecha_validacion')->nullable(); $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps(); $t->unique(['prospectos_id', 'rfc']);
        });
        (require database_path('migrations/2026_09_28_000002_create_auditoria_perfiles_fiscales_table.php'))->up();
        $user = DB::table('users')->insertGetId([]); $student = DB::table('prospectos')->insertGetId([]);
        $ids = [];
        foreach (['AAAA010101AA1', 'BBBB010101BB2'] as $rfc) $ids[] = DB::table('perfiles_fiscales')->insertGetId([
            'prospectos_id'=>$student, 'tipo_persona'=>'fisica', 'rfc'=>$rfc, 'nombre_razon_social'=>'Receptor',
            'codigo_postal_fiscal'=>'01000', 'regimen_fiscal'=>'605', 'uso_cfdi'=>'D10', 'correo_facturacion'=>'f@example.test',
            'relacion_alumno'=>'Madre', 'nivel_educativo'=>'Licenciatura', 'rvoe'=>'RVOE', 'predeterminado'=>false,
            'activo'=>true, 'created_by'=>$user, 'updated_by'=>$user, 'created_at'=>now(), 'updated_at'=>now(),
        ]);

        $dir = sys_get_temp_dir().'/epic17-'.bin2hex(random_bytes(5)); mkdir($dir, 0700, true);
        $script = $dir.'/worker.php';
        file_put_contents($script, <<<'PHP'
<?php
require $argv[1].'/vendor/autoload.php'; $app=require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); while(!file_exists($argv[2])) usleep(1000);
$p=App\Models\PerfilFiscal::findOrFail((int)$argv[3]); $data=$p->only(['prospectos_id','tipo_persona','rfc','nombre_razon_social','codigo_postal_fiscal','regimen_fiscal','uso_cfdi','correo_facturacion','relacion_alumno','curp','nivel_educativo','rvoe','activo','fecha_validacion']);
$data['predeterminado']=true; app(App\Services\Facturacion\PerfilFiscalService::class)->guardar($data,(int)$argv[4],$p);
PHP);
        $env = array_merge(array_filter($_SERVER, 'is_scalar'), array_filter($_ENV, 'is_scalar'), ['APP_ENV'=>'testing', 'DB_CONNECTION'=>'mysql',
            'DB_HOST'=>getenv('EPIC17_MYSQL_HOST'), 'DB_PORT'=>getenv('EPIC17_MYSQL_PORT'), 'DB_DATABASE'=>$database,
            'DB_USERNAME'=>getenv('EPIC17_MYSQL_USERNAME'), 'DB_PASSWORD'=>getenv('EPIC17_MYSQL_PASSWORD')]);
        $processes=[]; foreach ($ids as $id) $processes[]=proc_open([PHP_BINARY,$script,base_path(),$dir.'/go',(string)$id,(string)$user],
            [['pipe','r'],['file',$dir.'/out','a'],['file',$dir.'/err','a']],$pipes,base_path(),$env);
        touch($dir.'/go'); foreach ($processes as $process) $this->assertSame(0, proc_close($process), file_get_contents($dir.'/err'));
        $this->assertSame(1, PerfilFiscal::query()->where('prospectos_id', $student)->where('predeterminado', true)->count());
    }

    private function mysqlConfig(): array
    {
        return ['driver'=>'mysql','host'=>getenv('EPIC17_MYSQL_HOST'),'port'=>getenv('EPIC17_MYSQL_PORT'),
            'database'=>getenv('EPIC17_MYSQL_DATABASE'),'username'=>getenv('EPIC17_MYSQL_USERNAME'),'password'=>getenv('EPIC17_MYSQL_PASSWORD'),
            'charset'=>'utf8mb4','collation'=>'utf8mb4_unicode_ci','prefix'=>'','strict'=>true,'engine'=>null];
    }
}
