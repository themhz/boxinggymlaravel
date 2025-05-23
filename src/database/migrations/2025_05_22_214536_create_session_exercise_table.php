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
                  ->onDelete('cascade');
            $table->foreignId('exercise_id')
                  ->constrained('exercises')
                  ->onDelete('cascade');
            $table->timestamps();
            $table->unique(['session_id','exercise_id'], 'session_exercise_unique');
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
