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
        Schema::create('exercise_equipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exercise_id')
                ->constrained('exercises')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreignId('equipment_id')
                ->constrained('equipments')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->boolean('is_required')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_equipments');
    }
};
