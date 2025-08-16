<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->smallInteger('year');                 // e.g., 2025
            $table->tinyInteger('month');                 // 1..12
            $table->decimal('amount', 10, 2);             // gross or agreed salary
            $table->date('due_date')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestampTz('paid_at')->nullable();
            $table->string('method', 30)->nullable();     // cash, bank, revolut, etc.
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'year', 'month']); // one record per teacher per month
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_salaries');
    }
};