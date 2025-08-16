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
        Schema::create('class_teacher', function (Blueprint $table) {
        $table->id();
        $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
        $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
        $table->string('role', 30)->nullable();
        $table->boolean('is_primary')->default(false);
        $table->timestamps();
        $table->unique(['class_id','teacher_id'], 'class_teacher_unique');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_teacher');
    }
};
