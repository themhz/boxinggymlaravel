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
        Schema::create('session_exercise', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                  ->constrained('class_sessions')
                  ->cascadeOnDelete();
            $table->foreignId('exercise_id')
                  ->constrained('exercises')
                  ->cascadeOnDelete();

            $table->unsignedSmallInteger('display_order')->default(1);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['session_id','exercise_id','display_order'], 'session_exercise_unique');
            $table->index(['session_id','display_order']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_exercise');
    }
};
