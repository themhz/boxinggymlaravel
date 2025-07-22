<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AppointmentAvailability;
use App\Models\ClassException;
use Database\Seeders\AppointmentAvailabilitySeeder;

class DatabaseSeeder extends Seeder
{

    public function run()
    {        
         $this->call([
            UserSeeder::class,
            TeacherSeeder::class,    
            StudentSeeder::class,
            PaymentMethodsTableSeeder::class, 
            //SubscriptionSeeder::class,
            MembershipPlanSeeder::class,
            OfferSeeder::class,
            LessonSeeder::class,
            ClassSeeder::class,
            PaymentSeeder::class,
            TeacherSalarySeeder::class,
            LessonTeacherSeeder::class,
            ClassExceptionsTableSeeder::class,
            ClassSessionsTableSeeder::class,
            AttendancesTableSeeder::class,
            ClassStudentSeeder::class,
            ExerciseSeeder::class,
            StudentExerciseSeeder::class,

        ]);

    }
}
