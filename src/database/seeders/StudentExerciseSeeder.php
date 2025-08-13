<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Exercise;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;


class StudentExerciseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run(): void
    {
        $students   = Student::all();
        $exercises  = Exercise::all(); // has: id, name, exercise_type

        foreach ($students as $student) {
            // pick 2–5 unique exercises per student
            $pick = $exercises->random(rand(2, 5));

            // build attach payload: [exercise_id => [pivot attrs...]]
            $attach = [];

            foreach ($pick as $ex) {
                $attach[$ex->id] = $this->pivotForType($ex->exercise_type);
                // add a small note sometimes
                if (rand(0,1)) {
                    $attach[$ex->id]['note'] = match ($ex->exercise_type) {
                        'Cardio'     => 'Keep steady pace, nasal breathing',
                        'Resistance' => 'Tempo 3-1-1, full ROM',
                        'Elasticity' => 'Slow, controlled stretch',
                        default      => null,
                    };
                }
            }

            // attach while preserving existing ones (if any)
            $student->exercises()->syncWithoutDetaching($attach);
        }
    }

    /**
     * Generate realistic pivot attrs based on exercise_type.
     */
    private function pivotForType(string $type): array
    {
        return match ($type) {
            // time-based work
            'Cardio' => [
                'sets'             => null,
                'repetitions'      => null,
                'weight'           => null,
                // 5–30 minutes
                'duration_seconds' => rand(5, 30) * 60,
            ],

            // strength/conditioning
            'Resistance' => [
                'sets'             => rand(3, 5),
                'repetitions'      => rand(8, 20),
                // bodyweight or light load for boxing context
                'weight'           => Arr::random([null, 10, 12.5, 15, 20, 25, 30]),
                'duration_seconds' => null,
            ],

            // mobility/flexibility
            'Elasticity' => [
                'sets'             => rand(2, 4),
                // hold seconds per set
                'repetitions'      => null,
                'weight'           => null,
                'duration_seconds' => Arr::random([20, 30, 45, 60]),
            ],

            default => [
                'sets'             => null,
                'repetitions'      => null,
                'weight'           => null,
                'duration_seconds' => null,
            ],
        };
    }

}
