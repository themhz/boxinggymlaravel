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
        Schema::create('class_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')
                ->constrained('classes')
                ->onDelete('cascade');
            $table->date('exception_date');
            $table->boolean('is_cancelled')->default(true);
            $table->time('override_start_time')->nullable();
            $table->time('override_end_time')->nullable();
            $table->string('reason')->nullable(); // NEW
            $table->timestamps();

            $table->unique(['class_id', 'exception_date'], 'class_exception_unique');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_exceptions');
    }
};
