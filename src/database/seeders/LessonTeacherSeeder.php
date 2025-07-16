<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lesson;
use App\Models\Teacher;

class LessonTeacherSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = Lesson::all();
        $teachers = Teacher::all();

        foreach ($lessons as $lesson) {
            // Attach 1 to 3 random teachers per lesson
            $lesson->teachers()->syncWithoutDetaching(
                $teachers->random(rand(1, 3))->pluck('id')->toArray()
            );
        }
    }
}