<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AppointmentAvailability;
use Database\Seeders\AppointmentAvailabilitySeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    // public function run()
    // {
    //     \App\Models\Team::factory(3)->create()->each(function ($team) {
    //         \App\Models\Teacher::factory(2)->create(['team_id' => $team->id])->each(function ($teacher) {
    //             \App\Models\ClassType::factory(2)->create(['teacher_id' => $teacher->id])->each(function ($class) {
    //                 \App\Models\Program::factory(3)->create(['class_type_id' => $class->id])->each(function ($program) {
    //                     \App\Models\Student::factory(5)->create()->each(function ($student) use ($program) {
    //                         \App\Models\Appointment::factory()->create([
    //                             'student_id' => $student->id,
    //                             'program_id' => $program->id
    //                         ]);
    //                     });
    //                 });
    //             });
    //         });
    //     });
    // }

    public function run()
    {
        // $this->call([
        //     TeamSeeder::class,
        //     TeacherSeeder::class,
        //     UserSeeder::class,
        //     PostSeeder::class,          
        //     StudentSeeder::class,

        //     // add all your seeders here
        // ]);
        // $this->call([
        //     ClassSeeder::class,
        // ]);
         $this->call([
            UserSeeder::class,
            TeacherSeeder::class,    // ← first
            StudentSeeder::class,    // ← then students
            LessonSeeder::class,     // ← then lessons
            ClassesTableSeeder::class,
            MembershipPlanSeeder::class,
            OfferSeeder::class,            
            PaymentSeeder::class,
            LessonTeacherSeeder::class,


        ]);

    }
}
