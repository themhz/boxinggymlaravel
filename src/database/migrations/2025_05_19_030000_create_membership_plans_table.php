<?php
// database/migrations/2025_05_21_030000_create_membership_plans_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2);
            $table->integer('duration_days');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('membership_plans');
    }
};