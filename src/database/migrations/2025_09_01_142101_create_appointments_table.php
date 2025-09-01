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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('name');               // Full name of the newcomer
            $table->string('email')->nullable();  // Optional contact email
            $table->string('phone')->nullable();  // Optional contact phone
            $table->dateTime('scheduled_at');     // Date & time of the appointment
            $table->text('notes')->nullable();    // Optional notes or comments
            $table->string('status')->default('pending'); // pending, confirmed, cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
