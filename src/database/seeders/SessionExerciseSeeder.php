<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClassSession;
use App\Models\Exercise;
use App\Models\SessionExercise;
class SessionExerciseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sessions = ClassSession::all();
        $exerciseIds = Exercise::pluck('id')->toArray();

        foreach ($sessions as $session) {
            $randomExercises = collect($exerciseIds)->random(rand(2, 5))->toArray();

            foreach ($randomExercises as $exerciseId) {
                SessionExercise::firstOrCreate([
                    'session_id' => $session->id,
                    'exercise_id' => $exerciseId,
                ]);
            }
        }
    }
}
