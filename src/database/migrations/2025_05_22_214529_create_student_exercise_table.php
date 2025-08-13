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
        Schema::create('student_exercises', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained('exercises')->onDelete('cascade');

            // personalized per-student fields
            $table->unsignedInteger('sets')->nullable();
            $table->unsignedInteger('repetitions')->nullable();      // per-set target/actual
            $table->decimal('weight', 6, 2)->nullable();              // kg
            $table->unsignedInteger('duration_seconds')->nullable();  // holds / cardio            
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'exercise_id'], 'student_exercise_unique');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_exercises');
    }
};
