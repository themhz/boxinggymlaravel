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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                ->constrained('class_sessions')
                ->onDelete('cascade');
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');
            $table->enum('status', ['present','absent'])->default('present');
            $table->timestamps();

            $table->unique(['session_id','student_id'], 'attendance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
