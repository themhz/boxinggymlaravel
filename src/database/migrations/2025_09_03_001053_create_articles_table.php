<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                  ->constrained('categories')
                  ->cascadeOnDelete();

            // optional author if you want to attribute posts to users (nullable if route can be public)
            $table->foreignId('author_id')->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('excerpt', 300)->nullable();
            $table->text('content'); // markdown or html
            $table->string('status', 20)->default('draft'); // draft|published|archived
            $table->dateTime('published_at')->nullable();
            $table->string('featured_image_url', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
