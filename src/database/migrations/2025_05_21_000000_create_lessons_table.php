<?php
// database/migrations/2025_05_21_000000_create_lessons_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('level')->nullable();
            $table->string('image')->nullable();            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lessons');
    }
};
