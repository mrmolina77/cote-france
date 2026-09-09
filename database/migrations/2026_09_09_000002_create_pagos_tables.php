<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consecutivos_pago', function (Blueprint $table) {
            $table->unsignedSmallInteger('anio')->primary();
            $table->unsignedBigInteger('ultimo_consecutivo')->default(0);
            $table->timestamps();
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id('pago_id');
            $table->string('folio')->unique();
            $table->unsignedBigInteger('inscripciones_id');
            $table->unsignedBigInteger('prospectos_id');
            $table->unsignedBigInteger('responsable_pago_id');
            $table->dateTime('fecha_pago');
            $table->string('zona_horaria', 64);
            $table->char('moneda', 3)->default('MXN');
            $table->decimal('tipo_cambio', 18, 6)->default('1.000000');
            $table->decimal('monto', 12, 2);
            $table->unsignedBigInteger('metodo_pago_id');
            $table->char('forma_pago_sat', 2)->nullable();
            $table->string('banco', 120)->nullable();
            $table->string('referencia', 120)->nullable();
            $table->string('numero_cheque', 50)->nullable();
            $table->string('rastreo_spei', 100)->nullable();
            $table->string('numero_autorizacion', 100)->nullable();
            $table->string('terminal', 100)->nullable();
            $table->string('ultimos_4_digitos', 4)->nullable();
            $table->string('proveedor', 120)->nullable();
            $table->unsignedBigInteger('anticipo_relacionado_id')->nullable();
            $table->string('identificador_transaccion_externa')->nullable();
            $table->dateTime('fecha_movimiento')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 20)->default('borrador');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->dateTime('fecha_confirmacion')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->dateTime('fecha_cancelacion')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->dateTime('fecha_reembolso')->nullable();
            $table->timestamps();

            $table->foreign('inscripciones_id')->references('inscripciones_id')->on('inscripciones')->restrictOnDelete();
            $table->foreign('prospectos_id')->references('prospectos_id')->on('prospectos')->restrictOnDelete();
            $table->foreign('responsable_pago_id')->references('responsable_pago_id')->on('responsables_pago')->restrictOnDelete();
            $table->foreign('metodo_pago_id')->references('metodo_pago_id')->on('metodos_pago')->restrictOnDelete();
            $table->foreign('anticipo_relacionado_id')->references('pago_id')->on('pagos')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('confirmed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['inscripciones_id', 'estado']);
            $table->index(['prospectos_id', 'estado']);
            $table->index(['fecha_pago', 'estado']);
            $table->index(['metodo_pago_id', 'fecha_pago']);
            $table->index('responsable_pago_id');
            $table->index('referencia');
            $table->index('rastreo_spei');
            $table->index('identificador_transaccion_externa');
            $table->index('anticipo_relacionado_id');
        });
    }

    public function down(): void
    {
        // SQLite cannot alter foreign keys. Dropping the table removes them atomically.
        if (DB::getDriverName() !== 'sqlite' && Schema::hasTable('pagos')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->dropForeign(['inscripciones_id']);
                $table->dropForeign(['prospectos_id']);
                $table->dropForeign(['responsable_pago_id']);
                $table->dropForeign(['metodo_pago_id']);
                $table->dropForeign(['anticipo_relacionado_id']);
                $table->dropForeign(['created_by']);
                $table->dropForeign(['confirmed_by']);
                $table->dropForeign(['cancelled_by']);
            });
        }

        Schema::dropIfExists('pagos');
        Schema::dropIfExists('consecutivos_pago');
    }
};
