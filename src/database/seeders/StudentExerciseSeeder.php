<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Exercise;
use Illuminate\Support\Facades\DB;
class StudentExerciseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::all();
        $exerciseIds = Exercise::pluck('id')->toArray();

        foreach ($students as $student) {
            $randomExercises = collect($exerciseIds)->random(rand(2, 5))->toArray();
            $student->exercises()->syncWithoutDetaching($randomExercises);
        }
    }
}
