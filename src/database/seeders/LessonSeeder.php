<?php
// database/seeders/LessonSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LessonSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        DB::table('lessons')->insert([
            [
                'title'       => 'Boxing',
                'description' => 'Learn fundamentals of boxing: stance, footwork, punches, defense and combos.',
                'level'       => 'Beginner',
                'image'       => '/templates/boxinggym/boxinggym1/assets/img/boxing3.jpg',                
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'title'       => 'Kickboxing',
                'description' => 'Combine punches and kicks in this high-intensity martial arts workout.',
                'level'       => 'Intermediate',
                'image'       => '/templates/boxinggym/boxinggym1/assets/img/kickboxing.jpg',                
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'title'       => 'Judo',
                'description' => 'Master throws, holds and groundwork in this traditional Japanese martial art.',
                'level'       => 'All Levels',
                'image'       => '/templates/boxinggym/boxinggym1/assets/img/judo.jpg',                
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }
}
