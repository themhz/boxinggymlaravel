<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
            MembershipPlanSeeder::class,
            OfferSeeder::class,
            LessonSeeder::class,
            ClassSeeder::class,
            PaymentSeeder::class,
            TeacherSalarySeeder::class,            
            ClassSessionsTableSeeder::class,
            AttendancesTableSeeder::class,
            ClassStudentSeeder::class,
            ExerciseSeeder::class,
            StudentExerciseSeeder::class,            
            ClassExceptionSeeder::class,
            ClassTeacherSeeder::class,
            SessionExerciseSeeder::class,            
            SessionExerciseStudentsSeeder::class
        ]);

        // Create or update the admin user
        User::updateOrCreate(
            ['email' => 'themhz@gmail.com'],
            [
                'name'     => 'Themis Theotokatos',
                'password' => Hash::make('526996'), // or change if needed
                'role'     => 'admin',
            ]
        );

    }
}
