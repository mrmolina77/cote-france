<?php

namespace Tests\Feature;

use App\Models\Inscripcion;
use App\Models\ResponsablePago;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InscripcionesFinancialMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::statement('PRAGMA foreign_keys = ON');

        Schema::create('prospectos', fn (Blueprint $table) => $table->id('prospectos_id'));
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id('inscripciones_id');
            $table->date('fecha_inscripcion');
            $table->unsignedBigInteger('prospectos_id');
            $table->unsignedBigInteger('cursos_id');
            $table->unsignedBigInteger('grupo_id');
            $table->timestamps();
            $table->softDeletes();
        });

        $this->responsablesMigration()->up();
    }

    public function test_up_preserves_historical_data_with_only_mxn_default(): void
    {
        DB::table('inscripciones')->insert([
            'inscripciones_id' => 1,
            'fecha_inscripcion' => '2025-01-10',
            'prospectos_id' => 10,
            'cursos_id' => 20,
            'grupo_id' => 30,
        ]);

        $this->financialMigration()->up();

        $this->assertTrue(Schema::hasColumns('inscripciones', $this->financialColumns()));
        $row = (array) DB::table('inscripciones')->find(1);
        $this->assertSame('MXN', $row['moneda']);
        foreach (array_diff($this->financialColumns(), ['moneda']) as $column) {
            $this->assertNull($row[$column], $column.' debe permanecer NULL para históricos.');
        }
        $this->assertSame('2025-01-10', $row['fecha_inscripcion']);
    }

    public function test_decimal_precision_and_monthly_payment_counts_are_stored(): void
    {
        $this->financialMigration()->up();

        foreach ([null, 0, 1, 12, 120] as $index => $count) {
            DB::table('inscripciones')->insert([
                'fecha_inscripcion' => '2026-08-16',
                'prospectos_id' => $index + 1,
                'cursos_id' => 1,
                'grupo_id' => 1,
                'monto_inscripcion' => '1500.50',
                'monto_mensualidad' => '2500.75',
                'descuento' => '10.50',
                'beca' => '25.75',
                'numero_mensualidades' => $count,
            ]);
        }

        $rows = Inscripcion::orderBy('inscripciones_id')->get();
        $this->assertSame([null, 0, 1, 12, 120], $rows->pluck('numero_mensualidades')->all());
        $this->assertSame('1500.50', $rows->first()->monto_inscripcion);
        $this->assertSame('2500.75', $rows->first()->monto_mensualidad);
        $this->assertSame('10.50', $rows->first()->descuento);
        $this->assertSame('25.75', $rows->first()->beca);
    }

    public function test_user_foreign_keys_null_on_delete_and_responsable_restricts_delete(): void
    {
        $this->financialMigration()->up();
        DB::table('users')->insert(['id' => 1]);
        $responsable = ResponsablePago::create(['tipo' => 'empresa', 'nombre_razon_social' => 'Empresa']);
        DB::table('inscripciones')->insert([
            'fecha_inscripcion' => '2026-08-16', 'prospectos_id' => 1, 'cursos_id' => 1, 'grupo_id' => 1,
            'responsable_pago_id' => $responsable->getKey(), 'created_by' => 1, 'updated_by' => 1,
        ]);

        DB::table('users')->delete(1);
        $row = DB::table('inscripciones')->first();
        $this->assertNull($row->created_by);
        $this->assertNull($row->updated_by);

        $this->expectException(QueryException::class);
        $responsable->delete();
    }

    public function test_models_expose_payment_responsible_relations_for_person_and_company(): void
    {
        $this->financialMigration()->up();
        DB::table('prospectos')->insert(['prospectos_id' => 5]);
        $persona = ResponsablePago::create([
            'tipo' => 'persona', 'prospectos_id' => 5, 'nombre_razon_social' => 'Estudiante',
        ]);
        ResponsablePago::create(['tipo' => 'empresa', 'nombre_razon_social' => 'Empresa externa']);
        $inscripcion = Inscripcion::create([
            'fecha_inscripcion' => '2026-08-16', 'prospectos_id' => 5, 'cursos_id' => 1, 'grupo_id' => 1,
            'responsable_pago_id' => $persona->getKey(),
        ]);

        $this->assertTrue($inscripcion->responsablePago->is($persona));
        $this->assertNotNull($persona->prospecto);
        $this->assertTrue($persona->inscripciones->first()->is($inscripcion));
        $this->assertNull(ResponsablePago::where('tipo', 'empresa')->first()->prospecto);
    }

    public function test_down_removes_only_financial_fields_and_preserves_historical_table(): void
    {
        $migration = $this->financialMigration();
        $migration->up();
        $migration->down();

        $this->assertTrue(Schema::hasTable('inscripciones'));
        $this->assertTrue(Schema::hasColumns('inscripciones', [
            'inscripciones_id', 'fecha_inscripcion', 'prospectos_id', 'cursos_id', 'grupo_id',
            'created_at', 'updated_at', 'deleted_at',
        ]));
        foreach ($this->financialColumns() as $column) {
            $this->assertFalse(Schema::hasColumn('inscripciones', $column));
        }

        $this->responsablesMigration()->down();
        $this->assertFalse(Schema::hasTable('responsables_pago'));
    }

    private function financialColumns(): array
    {
        return [
            'estatus', 'fecha_inicio', 'fecha_fin', 'moneda', 'monto_inscripcion',
            'monto_mensualidad', 'dia_vencimiento', 'numero_mensualidades', 'descuento',
            'beca', 'observaciones_financieras', 'responsable_pago_id', 'created_by', 'updated_by',
        ];
    }

    private function responsablesMigration()
    {
        return require database_path('migrations/2026_08_16_000001_create_responsables_pago_table.php');
    }

    private function financialMigration()
    {
        return require database_path('migrations/2026_08_16_000002_add_financial_fields_to_inscripciones_table.php');
    }
}
