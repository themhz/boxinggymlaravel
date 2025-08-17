<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\ClassSession;

class AttendancesTableSeeder extends Seeder
{
    public function run()
    {
         // Example: mark student #1 present for session #1
        Attendance::create([
            'session_id' => 1,
            'student_id' => 1,
            'status'     => 'present',
            'note'       => 'Arrived on time',
        ]);
        // Example: mark student #2 absent for session #1
        Attendance::create([
            'session_id' => 1,
            'student_id' => 2,
            'status'     => 'absent',
            'note'       => 'Sick',            
        ]);
        // Example: mark student #3 present for session #2
        Attendance::create([
            'session_id' => 2,
            'student_id' => 3,
            'status'     => 'present',
            'note'       => 'On time',
        ]);
        // Example: mark student #1 absent for session #2
        Attendance::create([
            'session_id' => 2,
            'student_id' => 1,
            'status'     => 'absent',
            'note'       => 'Family emergency',
        ]);
    }
}
