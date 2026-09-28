<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles_fiscales', function (Blueprint $table) {
            $table->id('perfil_fiscal_id');
            $table->unsignedBigInteger('prospectos_id');
            $table->string('tipo_persona', 10);
            $table->string('rfc', 13);
            $table->string('nombre_razon_social', 255);
            $table->char('codigo_postal_fiscal', 5);
            $table->string('regimen_fiscal', 10);
            $table->string('uso_cfdi', 10);
            $table->string('correo_facturacion', 254);
            $table->string('relacion_alumno', 120);
            $table->char('curp', 18)->nullable();
            $table->string('nivel_educativo', 120);
            $table->string('rvoe', 120);
            $table->boolean('predeterminado')->default(false);
            $table->boolean('activo')->default(true);
            $table->date('fecha_validacion')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('prospectos_id')->references('prospectos_id')->on('prospectos')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['prospectos_id', 'rfc'], 'perfiles_alumno_rfc_unique');
            $table->index(['prospectos_id', 'activo', 'predeterminado'], 'perfiles_alumno_estado_idx');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->boolean('solicita_factura')->default(false)->after('observaciones');
            $table->unsignedBigInteger('perfil_fiscal_id')->nullable()->after('solicita_factura');
            $table->json('perfil_fiscal_snapshot')->nullable()->after('perfil_fiscal_id');
            $table->foreign('perfil_fiscal_id')->references('perfil_fiscal_id')->on('perfiles_fiscales')->restrictOnDelete();
            $table->index('perfil_fiscal_id');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite' && Schema::hasTable('pagos')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->dropForeign(['perfil_fiscal_id']);
                $table->dropIndex(['perfil_fiscal_id']);
            });
        }
        Schema::table('pagos', fn (Blueprint $table) => $table->dropColumn(['solicita_factura', 'perfil_fiscal_id', 'perfil_fiscal_snapshot']));
        Schema::dropIfExists('perfiles_fiscales');
    }
};
