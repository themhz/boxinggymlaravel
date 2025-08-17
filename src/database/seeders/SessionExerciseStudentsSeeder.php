<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{SessionExerciseStudent, ClassSession, Student, SessionExercise, StudentExercise};

class SessionExerciseStudentsSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = ClassSession::take(3)->get();
        $students = Student::take(5)->get();

        foreach ($sessions as $session) {
            foreach ($students as $student) {
                $sessEx = SessionExercise::where('session_id', $session->id)->inRandomOrder()->first();
                $studEx = StudentExercise::where('student_id', $student->id)->inRandomOrder()->first();

                SessionExerciseStudent::create([
                    'session_id'                 => $session->id,
                    'student_id'                 => $student->id,
                    'session_exercise_id'        => $sessEx?->id,
                    'student_exercise_id'        => $studEx?->id,
                    'performed_sets'             => rand(2,5),
                    'performed_repetitions'      => rand(8,15),
                    'performed_weight'           => rand(10,40),
                    'performed_duration_seconds' => rand(60,300),
                    'status'                     => ['completed','skipped','partial'][rand(0,2)],
                ]);
            }
        }
    }
}
