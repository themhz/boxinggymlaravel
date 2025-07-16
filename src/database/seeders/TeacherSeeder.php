<?php
// database/seeders/TeacherSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Teacher;

use Carbon\Carbon;

class TeacherSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        // Create teachers without user_id
        $teachers = [
            ['name' => 'John Doe', 'email' => 'john.doe@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane.smith@example.com'],
            ['name' => 'Mike Johnson', 'email' => 'mike.johnson@example.com'],
        ];

        foreach ($teachers as $t) {
            Teacher::firstOrCreate(
                ['email' => $t['email']],
                ['name' => $t['name'], 'created_at' => $now, 'updated_at' => $now]
            );
        }

        // Create users and associate them
        $users = [
            [
                'email' => 'john.user@example.com',
                'name' => 'JohnUser',
                'teacher_email' => 'john.doe@example.com',
            ],
            [
                'email' => 'jane.user@example.com',
                'name' => 'JaneUser',
                'teacher_email' => 'jane.smith@example.com',
            ],
            [
                'email' => 'mike.user@example.com',
                'name' => 'MikeUser',
                'teacher_email' => 'mike.johnson@example.com',
            ],
        ];

        foreach ($users as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                ['name' => $u['name'], 'password' => Hash::make('password')]
            );

            // Update the teacher's user_id
            $teacher = Teacher::where('email', $u['teacher_email'])->first();
            if ($teacher && $teacher->user_id === null) {
                $teacher->user_id = $user->id;
                $teacher->save();
            }
        }
    }
}
