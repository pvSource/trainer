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
        Schema::create('measurement_muscles', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('muscle_id');
            $table->foreign('muscle_id')
                ->references('id')
                ->on('muscles')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreignId('measurement_id')
                ->constrained('measurements')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurement_muscles');
    }
};
