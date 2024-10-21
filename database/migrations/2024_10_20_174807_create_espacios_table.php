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
        Schema::create('espacios', function (Blueprint $table) {
            $table->id('espacios_id');
            $table->string('espacios_nombre',100); 
            $table->string('espacios_descripcion',250);
            $table->boolean('espacios_activo');
            $table->unsignedBigInteger('modalidad_id');
            $table->foreign('modalidad_id')->references('modalidad_id')->on('modalidades');
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
        Schema::dropIfExists('espacios');
    }
};
