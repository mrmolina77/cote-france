<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class PagosTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::statement('PRAGMA foreign_keys = ON');
        $this->createReferencedTables();
        $this->pagosMigration()->up();
    }

    protected function createReferences(): array
    {
        $prospecto = DB::table('prospectos')->insertGetId([]);
        $responsable = DB::table('responsables_pago')->insertGetId(['prospectos_id' => $prospecto]);
        $inscripcion = DB::table('inscripciones')->insertGetId(['prospectos_id' => $prospecto, 'responsable_pago_id' => $responsable]);
        $metodo = DB::table('metodos_pago')->insertGetId(['clave' => uniqid('MET-'), 'nombre' => 'Método']);

        return compact('prospecto', 'responsable', 'inscripcion', 'metodo');
    }

    protected function paymentAttributes(array $overrides = []): array
    {
        $references = $this->createReferences();

        return array_merge([
            'folio' => uniqid('PAG-2026-'),
            'inscripciones_id' => $references['inscripcion'],
            'prospectos_id' => $references['prospecto'],
            'responsable_pago_id' => $references['responsable'],
            'fecha_pago' => '2026-09-09 12:00:00',
            'zona_horaria' => 'America/Mexico_City',
            'monto' => '123.45',
            'metodo_pago_id' => $references['metodo'],
        ], $overrides);
    }

    protected function pagosMigration()
    {
        return require database_path('migrations/2026_09_09_000002_create_pagos_tables.php');
    }

    private function createReferencedTables(): void
    {
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('prospectos', fn (Blueprint $table) => $table->id('prospectos_id'));
        Schema::create('responsables_pago', function (Blueprint $table) {
            $table->id('responsable_pago_id');
            $table->unsignedBigInteger('prospectos_id');
            $table->timestamps();
        });
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id('inscripciones_id');
            $table->unsignedBigInteger('prospectos_id');
            $table->unsignedBigInteger('responsable_pago_id');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id('metodo_pago_id');
            $table->string('clave')->unique();
            $table->string('nombre');
            $table->timestamps();
        });
    }
}
