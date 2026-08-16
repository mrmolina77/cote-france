<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->string('estatus', 20)->nullable()->index();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->char('moneda', 3)->default('MXN');
            $table->decimal('monto_inscripcion', 12, 2)->nullable();
            $table->decimal('monto_mensualidad', 12, 2)->nullable();
            $table->unsignedTinyInteger('dia_vencimiento')->nullable();
            $table->unsignedSmallInteger('numero_mensualidades')->nullable();
            $table->decimal('descuento', 5, 2)->nullable();
            $table->decimal('beca', 5, 2)->nullable();
            $table->text('observaciones_financieras')->nullable();
            $table->unsignedBigInteger('responsable_pago_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->foreign('responsable_pago_id')
                ->references('responsable_pago_id')
                ->on('responsables_pago')
                ->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropForeign(['responsable_pago_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropIndex(['estatus']);
            $table->dropIndex(['responsable_pago_id']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            $table->dropColumn([
                'estatus',
                'fecha_inicio',
                'fecha_fin',
                'moneda',
                'monto_inscripcion',
                'monto_mensualidad',
                'dia_vencimiento',
                'numero_mensualidades',
                'descuento',
                'beca',
                'observaciones_financieras',
                'responsable_pago_id',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
