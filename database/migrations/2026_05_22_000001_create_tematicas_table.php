<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tematicas', function (Blueprint $table) {
            $table->id('tematica_id');
            $table->unsignedBigInteger('capitulo_id');
            $table->string('tematica_descripcion', 255);
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('tematica_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('capitulo_id')
                ->references('capitulo_id')
                ->on('capitulos')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tematicas');
    }
};
