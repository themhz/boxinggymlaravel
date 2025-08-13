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
                // Boxing-specific
                ['name' => 'Jab-Cross Combo',   'description' => 'Basic punch combo',       'exercise_type' => 'Resistance'],
                ['name' => 'Uppercut Drill',    'description' => 'Fast uppercuts',          'exercise_type' => 'Resistance'],
                ['name' => 'Push Ups',          'description' => 'Classic chest exercise',  'exercise_type' => 'Resistance'],
                ['name' => 'Jump Rope',         'description' => 'Cardio warm-up',          'exercise_type' => 'Cardio'],
                ['name' => 'Shadow Boxing',     'description' => 'Freestyle movement',      'exercise_type' => 'Cardio'],
                ['name' => 'Sit Ups',           'description' => 'Abdominal exercise',      'exercise_type' => 'Resistance'],
                ['name' => 'Plank',             'description' => 'Core hold',               'exercise_type' => 'Resistance'],
                ['name' => 'Burpees',           'description' => 'Full body move',          'exercise_type' => 'Cardio'],
                ['name' => 'Neck Rolls',        'description' => 'Neck mobility',           'exercise_type' => 'Elasticity'],
                ['name' => 'Mountain Climbers', 'description' => 'Cardio-core',             'exercise_type' => 'Cardio'],

                // General fitness
                ['name' => 'Running',           'description' => 'Outdoor running for endurance',    'exercise_type' => 'Cardio'],
                ['name' => 'Squats',            'description' => 'Lower body strength exercise',     'exercise_type' => 'Resistance'],
                ['name' => 'Hamstring Stretch', 'description' => 'Static stretch for hamstrings',    'exercise_type' => 'Elasticity'],
                ['name' => 'Bench Press',       'description' => 'Upper body push strength exercise','exercise_type' => 'Resistance']
            ];


        foreach ($exercises as $exercise) {
            Exercise::create($exercise);
        }
    }
}
