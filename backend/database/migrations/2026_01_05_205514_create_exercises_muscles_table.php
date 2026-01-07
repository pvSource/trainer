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
        Schema::create('exercises_muscles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exercise_id')
                ->constrained('exercises')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->unsignedSmallInteger('muscle_id');
            $table->foreign('muscle_id')
                ->references('id')
                ->on('muscles')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->boolean('is_primary')->default(true);

            $table->timestamps();
            $table->softDeletes();

            //Индексы
            $table->index('exercise_id');
            $table->index('muscle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises_muscles');
    }
};
