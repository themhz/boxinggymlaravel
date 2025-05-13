<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('appointment_availability', function (Blueprint $table) {
            $table->id();
            $table->string('day'); // e.g., "Mon", "Tue"
            $table->time('start_time'); // e.g., "09:00:00"
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_availability');
    }
};
