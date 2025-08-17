<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')
                  ->constrained('class_sessions')
                  ->cascadeOnDelete();

            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();

            // Optional extra info
            $table->enum('status', ['present','absent','late'])->default('present');
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['session_id','student_id']); // one attendance per student per session
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
