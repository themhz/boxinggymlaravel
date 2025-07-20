<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class TeacherSalarySeeder extends Seeder
{
    public function run(): void
    {
        $teachers = Teacher::with('user')->inRandomOrder()->take(10)->get();

        foreach ($teachers as $teacher) {
            for ($i = 0; $i < 3; $i++) {
                TeacherSalary::create([
                    'user_id' => $teacher->user_id,
                    'amount' => rand(500, 1000),
                    'pay_date' => Carbon::now()->subDays(rand(30 * $i + 1, 30 * ($i + 1))), // spaced like monthly payments
                    'note' => Arr::random([
                        'Monthly salary',
                        'Teaching bonus',
                        'Extra hours payment',
                        'Private session reward',
                    ]),
                ]);
            }
        }
    }
}
