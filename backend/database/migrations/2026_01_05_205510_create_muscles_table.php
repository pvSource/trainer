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
        Schema::create('muscles', function (Blueprint $table) {
            $table->smallIncrements('id');

            $table->unsignedSmallInteger('parent_id')->nullable();
            $table->foreign('parent_id')
                ->references('id')
                ->on('muscles')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedSmallInteger('level');

            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            //Индексы
            $table->index('name');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('muscles');
    }
};
