<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\ConceptoCobro;
use App\Models\Inscripcion;
use App\Models\Prospecto;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CargoModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::statement('PRAGMA foreign_keys = ON');
        $this->createSchema();
    }

    public function test_model_configuration_casts_and_states(): void
    {
        $cargo = new Cargo();
        $casts = $cargo->getCasts();

        $this->assertSame('cargos', $cargo->getTable());
        $this->assertSame('cargo_id', $cargo->getKeyName());
        foreach (['subtotal', 'descuento', 'recargo', 'impuestos', 'total', 'saldo_pendiente'] as $amount) {
            $this->assertSame('decimal:2', $casts[$amount]);
            $this->assertNotContains($casts[$amount], ['float', 'double']);
        }
        $this->assertSame('date:Y-m-d', $casts['fecha_emision']);
        $this->assertSame('date:Y-m-d', $casts['fecha_vencimiento']);
        $this->assertSame('integer', $casts['periodo_anio']);
        $this->assertSame('integer', $casts['periodo_mes']);
        $this->assertSame(['pendiente', 'parcial', 'pagado', 'vencido', 'cancelado'], Cargo::ESTADOS);
        $this->assertSame(Cargo::ESTADO_PENDIENTE, 'pendiente');
        $this->assertSame(Cargo::ESTADO_PARCIAL, 'parcial');
        $this->assertSame(Cargo::ESTADO_PAGADO, 'pagado');
        $this->assertSame(Cargo::ESTADO_VENCIDO, 'vencido');
        $this->assertSame(Cargo::ESTADO_CANCELADO, 'cancelado');
    }

    public function test_decimal_values_are_returned_as_two_decimal_strings(): void
    {
        [$inscripcion, $concepto] = $this->parents();
        $cargo = Cargo::create($this->attributes($inscripcion, $concepto, 'pendiente'));

        foreach (['subtotal', 'descuento', 'recargo', 'impuestos', 'total', 'saldo_pendiente'] as $amount) {
            $this->assertIsString($cargo->fresh()->{$amount});
            $this->assertMatchesRegularExpression('/^-?\d+\.\d{2}$/', $cargo->fresh()->{$amount});
        }
        $this->assertNull($cargo->periodo_anio);
        $this->assertNull($cargo->periodo_mes);
    }

    public function test_relations_link_cargo_inscripcion_prospecto_concept_and_creator(): void
    {
        [$inscripcion, $concepto, $prospecto] = $this->parents(true);
        $user = User::forceCreate(['name' => 'Auditor', 'email' => 'auditor@example.test', 'password' => 'secret']);
        $cargo = Cargo::create($this->attributes($inscripcion, $concepto, 'pendiente'));
        DB::table('cargos')->where('cargo_id', $cargo->getKey())->update(['created_by' => $user->getKey()]);
        $cargo->refresh();

        $this->assertTrue($cargo->inscripcion->is($inscripcion));
        $this->assertTrue($cargo->inscripcion->prospecto->is($prospecto));
        $this->assertTrue($cargo->conceptoCobro->is($concepto));
        $this->assertTrue($cargo->createdBy->is($user));
        $this->assertTrue($inscripcion->cargos->contains($cargo));
        $this->assertTrue($concepto->cargos->contains($cargo));
    }

    public function test_status_scopes_return_only_expected_cargos(): void
    {
        [$inscripcion, $concepto] = $this->parents();
        foreach (Cargo::ESTADOS as $estado) {
            Cargo::create($this->attributes($inscripcion, $concepto, $estado));
        }

        $this->assertSame(['pendiente'], Cargo::pendientes()->pluck('estado')->all());
        $this->assertSame(['vencido'], Cargo::vencidos()->pluck('estado')->all());
        $this->assertEqualsCanonicalizing(
            ['pendiente', 'parcial', 'vencido'],
            Cargo::abiertos()->pluck('estado')->all()
        );
    }

    private function attributes(Inscripcion $inscripcion, ConceptoCobro $concepto, string $estado): array
    {
        return [
            'inscripciones_id' => $inscripcion->getKey(), 'concepto_cobro_id' => $concepto->getKey(),
            'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-10',
            'subtotal' => '123.45', 'descuento' => '3.45', 'recargo' => '0.00',
            'impuestos' => '19.20', 'total' => '139.20', 'saldo_pendiente' => '139.20',
            'estado' => $estado,
        ];
    }

    private function parents(bool $returnProspecto = false): array
    {
        $prospecto = new Prospecto();
        $prospecto->save();
        $inscripcion = new Inscripcion(['prospectos_id' => $prospecto->getKey()]);
        $inscripcion->save();
        $concepto = ConceptoCobro::create(['clave' => uniqid('C'), 'nombre' => 'Concepto']);

        return $returnProspecto ? [$inscripcion, $concepto, $prospecto] : [$inscripcion, $concepto];
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password');
            $table->timestamps();
        });
        Schema::create('prospectos', function (Blueprint $table) {
            $table->id('prospectos_id'); $table->timestamps();
        });
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id('inscripciones_id'); $table->unsignedBigInteger('prospectos_id');
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('conceptos_cobro', function (Blueprint $table) {
            $table->id('concepto_cobro_id'); $table->string('clave'); $table->string('nombre');
            $table->timestamps();
        });
        (require database_path('migrations/2026_09_07_000001_create_cargos_table.php'))->up();
    }
}
