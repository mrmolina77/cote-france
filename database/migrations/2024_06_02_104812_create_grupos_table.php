<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id('grupo_id');
            $table->string('grupo_nombre',45);
            $table->string('grupo_nivel',45);
            $table->string('grupo_capitulo',45)->nullable();
            $table->text('grupo_libro_maestro')->nullable();
            $table->text('grupo_libro_alumno')->nullable();
            $table->text('grupo_observacion')->nullable();
            $table->unsignedBigInteger('modalidad_id');
            $table->unsignedBigInteger('estado_id');
            $table->foreign('modalidad_id')->references('modalidad_id')->on('modalidades');
            $table->foreign('estado_id')->references('estado_id')->on('estados');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grupos');
    }
};
