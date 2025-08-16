<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use App\Models\ClassModel; // assuming your model is named Classes
use Illuminate\Support\Facades\DB;

class ClassTeacherSeeder extends Seeder
{
    public function run(): void
    {
        // Clear table first (optional, only for dev)
        DB::table('class_teacher')->truncate();

        // Example: attach each teacher to some classes
        $teachers = Teacher::all();
        $classes  = ClassModel::all();

        if ($teachers->isEmpty() || $classes->isEmpty()) {
            $this->command->warn("⚠️ No teachers or classes found. Seed them first.");
            return;
        }

        // Just demo logic: first teacher teaches first 2 classes
        $firstTeacher = $teachers->first();
        $firstTeacher->classes()->attach([
            $classes[0]->id => ['role' => 'Head Coach', 'is_primary' => true],
            $classes[1]->id => ['role' => 'Assistant', 'is_primary' => false],
        ]);

        // Optionally attach all teachers to random classes
        foreach ($teachers as $teacher) {
            $randomClasses = $classes->random(min(2, $classes->count()));
            foreach ($randomClasses as $class) {
                $teacher->classes()->syncWithoutDetaching([
                    $class->id => ['role' => 'Coach']
                ]);
            }
        }

        $this->command->info("✅ ClassTeacherSeeder completed.");
    }
}
