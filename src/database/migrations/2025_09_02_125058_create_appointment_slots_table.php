<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_slots', function (Blueprint $table) {
            $table->id();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->unsignedInteger('capacity')->default(1);
            $table->boolean('is_captured')->default(false); // false = available, true = booked
            $table->foreignId('created_by')->nullable()
                  ->constrained('users')
                  ->nullOnDelete(); // if the admin user is deleted, keep the slot
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_slots');
    }
};
