<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoriesTableSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Blog',            'slug' => 'blog',            'description' => 'Blog posts'],
            ['name' => 'Website Content', 'slug' => 'website-content', 'description' => 'Static site pages'],
        ];

        foreach ($items as $it) {
            Category::firstOrCreate(['slug' => $it['slug']], $it);
        }
    }
}
