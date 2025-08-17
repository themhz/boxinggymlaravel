<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{SessionExercise, ClassSession, Exercise};

class SessionExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $sessions  = ClassSession::inRandomOrder()->take(3)->get();
        $exercises = Exercise::inRandomOrder()->take(5)->get();

        if ($sessions->isEmpty() || $exercises->isEmpty()) return;

        foreach ($sessions as $session) {
            $order = 1;
            foreach ($exercises as $ex) {
                SessionExercise::create([
                    'session_id'    => $session->id,
                    'exercise_id'   => $ex->id,
                    'display_order' => $order++,
                    'note'          => 'Auto-generated session exercise',
                ]);
            }
        }
    }
}
