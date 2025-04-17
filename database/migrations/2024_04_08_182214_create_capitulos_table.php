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
        Schema::create('capitulos', function (Blueprint $table) {
            $table->id('capitulo_id');
            $table->string('capitulo_descripcion',100);
            $table->string('capitulo_codigo',10);
            $table->unsignedBigInteger('nivel_id');
            $table->foreign('nivel_id')->references('nivel_id')->on('niveles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('capitulos');
    }
};
