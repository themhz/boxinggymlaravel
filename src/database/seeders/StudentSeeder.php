<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $studentData = [
            [
                'name'  => 'Eliezer Pagac',
                'email' => 'eliezer.pagac@example.com',
                'phone' => '123-456-7890',
                'dob'   => '1990-01-01',
                'image' => 'https://i.pravatar.cc/150?img=11',
            ],
            [
                'name'  => 'Dr. Raul Walter',
                'email' => 'raul.walter@example.com',
                'phone' => '321-654-0987',
                'dob'   => '1991-02-15',
                'image' => 'https://i.pravatar.cc/150?img=22',
            ],
            [
                'name'  => 'Van Stanton',
                'email' => 'van.stanton@example.com',
                'phone' => '456-789-1234',
                'dob'   => '1992-03-20',
                'image' => 'https://i.pravatar.cc/150?img=33',
            ],
            [
                'name'  => 'Ulises Bruen',
                'email' => 'ulises.bruen@example.com',
                'phone' => '789-012-3456',
                'dob'   => '1993-04-10',
                'image' => 'https://i.pravatar.cc/150?img=44',
            ],
            [
                'name'  => 'Rhea Ullrich',
                'email' => 'rhea.ullrich@example.com',
                'phone' => '012-345-6789',
                'dob'   => '1994-05-05',
                'image' => 'https://i.pravatar.cc/150?img=55',
            ],
            [
                'name'  => 'Adrian Larkin',
                'email' => 'adrian.larkin@example.com',
                'phone' => '654-321-7890',
                'dob'   => '1995-06-06',
                'image' => 'https://i.pravatar.cc/150?img=66',
            ],
            [
                'name'  => 'Chelsie Gaylord',
                'email' => 'chelsie.gaylord@example.com',
                'phone' => '987-654-3210',
                'dob'   => '1996-07-07',
                'image' => 'https://i.pravatar.cc/150?img=77',
            ],
            [
                'name'  => 'Judson Runte',
                'email' => 'judson.runte@example.com',
                'phone' => '789-789-7890',
                'dob'   => '1997-08-08',
                'image' => 'https://i.pravatar.cc/150?img=88',
            ],
            [
                'name'  => 'Constantin Predovic',
                'email' => 'constantin.predovic@example.com',
                'phone' => '111-222-3333',
                'dob'   => '1998-09-09',
                'image' => 'https://i.pravatar.cc/150?img=99',
            ],
            [
                'name'  => 'Dr. Rachelle Franecki',
                'email' => 'rachelle.franecki@example.com',
                'phone' => '444-555-6666',
                'dob'   => '1999-10-10',
                'image' => 'https://i.pravatar.cc/150?img=10',
            ],
        ];

        foreach ($studentData as $data) {
            // Create user first with role 'student'
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('password'),
                    'role'     => 'student',
                ]
            );

            // Create linked student
            Student::create([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'dob'        => $data['dob'],
                'image'      => $data['image'],
                'user_id'    => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
