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
            $table->unsignedBigInteger('nivel_id')->nullable();
            $table->unsignedBigInteger('capitulo_id')->nullable();
            $table->text('grupo_libro_maestro')->nullable();
            $table->text('grupo_libro_alumno')->nullable();
            $table->text('grupo_observacion')->nullable();
            $table->unsignedBigInteger('modalidad_id');
            $table->unsignedBigInteger('estado_id')->default(1);
            $table->foreign('modalidad_id')->references('modalidad_id')->on('modalidades');
            $table->foreign('estado_id')->references('estado_id')->on('estados');
            $table->foreign('nivel_id')->references('nivel_id')->on('niveles');
            $table->foreign('capitulo_id')->references('capitulo_id')->on('capitulos');
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
