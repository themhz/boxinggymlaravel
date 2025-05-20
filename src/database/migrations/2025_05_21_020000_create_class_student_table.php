<?php
// database/migrations/2025_05_21_020000_create_class_student_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('class_student', function (Blueprint $table) {
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->primary(['class_id','student_id']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('class_student');
    }
};