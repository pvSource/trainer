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
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->string('name')->nullable();
            $table->text('description')->nullable();

            $table->dateTime('start_at')->nullable();
            $table->dateTime('finish_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            //Индексы
            $table->index('name');
            $table->index('start_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
