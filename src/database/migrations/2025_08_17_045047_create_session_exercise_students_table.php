<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('session_exercise_students', function (Blueprint $t) {
            $t->id();

            // Core links
            $t->foreignId('session_id')->constrained('class_sessions')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            // Optional links to plans            
            $t->foreignId('session_exercise_id')->nullable()->constrained('session_exercise')->nullOnDelete();
            $t->foreignId('student_exercise_id')->nullable()->constrained('student_exercises')->nullOnDelete();

            // What was actually performed (totals/aggregate)
            $t->unsignedSmallInteger('performed_sets')->nullable();
            $t->unsignedSmallInteger('performed_repetitions')->nullable();
            $t->decimal('performed_weight', 8, 2)->nullable(); // kg
            $t->unsignedInteger('performed_duration_seconds')->nullable();

            // Status of the outcome
            $t->enum('status', ['completed', 'skipped', 'partial'])->default('partial');

            $t->timestamps();

            // Helpful indexes
            $t->index(['session_id', 'student_id']);
            $t->index(['session_id', 'session_exercise_id']);
            $t->index(['student_id', 'student_exercise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_exercise_students');
    }
};
