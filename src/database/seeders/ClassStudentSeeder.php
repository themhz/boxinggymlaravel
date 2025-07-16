<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\ClassModel;

class ClassStudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::all();
        $classes = ClassModel::all();

        // Attach 1 to 3 random classes for each student
        foreach ($students as $student) {
            $student->classes()->syncWithoutDetaching(
                $classes->random(rand(1, 3))->pluck('id')->toArray()
            );
        }
    }
}