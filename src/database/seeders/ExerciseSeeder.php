<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Exercise;

class ExerciseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
        public function run(): void
        {
            $exercises = [
            ['name' => 'Jab-Cross Combo', 'sets' => 3, 'repetitions' => 20, 'description' => 'Basic punch combo'],
            ['name' => 'Uppercut Drill', 'sets' => 4, 'repetitions' => 15, 'description' => 'Fast uppercuts'],
            ['name' => 'Push Ups', 'sets' => 4, 'repetitions' => 20, 'description' => 'Classic chest exercise'],
            ['name' => 'Jump Rope', 'sets' => 3, 'repetitions' => 60, 'description' => 'Cardio warm-up'],
            ['name' => 'Shadow Boxing', 'sets' => 4, 'repetitions' => 180, 'description' => 'Freestyle movement'],
            ['name' => 'Sit Ups', 'sets' => 4, 'repetitions' => 25, 'description' => 'Abdominal exercise'],
            ['name' => 'Plank', 'sets' => 3, 'repetitions' => 60, 'description' => 'Core hold'],
            ['name' => 'Burpees', 'sets' => 4, 'repetitions' => 10, 'description' => 'Full body move'],
            ['name' => 'Neck Rolls', 'sets' => 3, 'repetitions' => 10, 'description' => 'Neck mobility'],
            ['name' => 'Mountain Climbers', 'sets' => 4, 'repetitions' => 30, 'description' => 'Cardio-core'],
        ];

        foreach ($exercises as $exercise) {
            Exercise::create($exercise);
        }
    }
}
