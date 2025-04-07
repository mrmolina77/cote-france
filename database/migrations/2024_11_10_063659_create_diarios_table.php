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
        Schema::create('diarios', function (Blueprint $table) {
            $table->id('diarios_id');
            $table->unsignedBigInteger('horarios_id')->nullable();
            $table->unsignedBigInteger('modalidades_id')->nullable();
            $table->longText('diarios_descripcion');
            $table->foreign('horarios_id')->references('horarios_id')->on('horarios');
            $table->foreign('modalidades_id')->references('modalidad_id')->on('modalidades');
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
        Schema::dropIfExists('diarios');
    }
};
