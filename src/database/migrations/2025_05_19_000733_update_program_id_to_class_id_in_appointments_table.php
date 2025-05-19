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
        Schema::table('appointments', function (Blueprint $table) {
            // Drop the old foreign key
            $table->dropForeign(['program_id_foreign']);

            // Rename column
            $table->renameColumn('program_id', 'class_id');
        });

        Schema::table('appointments', function (Blueprint $table) {
            // Add the new FK
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['program_id_foreign']);
            $table->renameColumn('class_id', 'program_id');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('program_id')->references('id')->on('classes')->onDelete('cascade');
        });
    }
};
