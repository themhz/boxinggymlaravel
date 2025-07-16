<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;

class StudentSeeder extends Seeder
{
   public function run(): void
    {
        $now = Carbon::now();

        // Step 1: Insert students
        DB::table('students')->insert([
            [
                'name'       => 'Eliezer Pagac',
                'email'      => 'eliezer.pagac@example.com',
                'phone'      => '123-456-7890',
                'dob'        => '1990-01-01',
                'image'      => 'https://i.pravatar.cc/150?img=11',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Dr. Raul Walter',
                'email'      => 'raul.walter@example.com',
                'phone'      => '321-654-0987',
                'dob'        => '1991-02-15',
                'image'      => 'https://i.pravatar.cc/150?img=22',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Van Stanton',
                'email'      => 'van.stanton@example.com',
                'phone'      => '456-789-1234',
                'dob'        => '1992-03-20',
                'image'      => 'https://i.pravatar.cc/150?img=33',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Ulises Bruen',
                'email'      => 'ulises.bruen@example.com',
                'phone'      => '789-012-3456',
                'dob'        => '1993-04-10',
                'image'      => 'https://i.pravatar.cc/150?img=44',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Rhea Ullrich',
                'email'      => 'rhea.ullrich@example.com',
                'phone'      => '012-345-6789',
                'dob'        => '1994-05-05',
                'image'      => 'https://i.pravatar.cc/150?img=55',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Adrian Larkin',
                'email'      => 'adrian.larkin@example.com',
                'phone'      => '654-321-7890',
                'dob'        => '1995-06-06',
                'image'      => 'https://i.pravatar.cc/150?img=66',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Chelsie Gaylord',
                'email'      => 'chelsie.gaylord@example.com',
                'phone'      => '987-654-3210',
                'dob'        => '1996-07-07',
                'image'      => 'https://i.pravatar.cc/150?img=77',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Judson Runte',
                'email'      => 'judson.runte@example.com',
                'phone'      => '789-789-7890',
                'dob'        => '1997-08-08',
                'image'      => 'https://i.pravatar.cc/150?img=88',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Constantin Predovic',
                'email'      => 'constantin.predovic@example.com',
                'phone'      => '111-222-3333',
                'dob'        => '1998-09-09',
                'image'      => 'https://i.pravatar.cc/150?img=99',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Dr. Rachelle Franecki',
                'email'      => 'rachelle.franecki@example.com',
                'phone'      => '444-555-6666',
                'dob'        => '1999-10-10',
                'image'      => 'https://i.pravatar.cc/150?img=10',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Step 2 & 3: Create users and associate
        $students = Student::all();

        foreach ($students as $student) {
            $user = User::firstOrCreate(
                ['email' => $student->email],
                ['name' => explode('@', $student->email)[0], 'password' => Hash::make('password')]
            );

            $student->update(['user_id' => $user->id]);
        }
    }
}
