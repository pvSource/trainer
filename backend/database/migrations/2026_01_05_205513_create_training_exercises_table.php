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
        Schema::create('training_exercises', function (Blueprint $table) {
            $table->id();

            $table->foreignId('training_id')
                ->constrained('trainings')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreignId('exercise_id')
                ->constrained('exercises')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->unsignedTinyInteger('approaches_count');
            $table->unsignedSmallInteger('repetitions_count');

            $table->decimal('weight', 6, 2);

            $table->timestamps();
            $table->softDeletes();

            //Индексы
            $table->index(['training_id', 'exercise_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_exercises');
    }
};
