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
        $teachers = Teacher::inRandomOrder()->take(10)->get();

        foreach ($teachers as $teacher) {
            for ($i = 0; $i < 3; $i++) {
                $date = Carbon::now()->subMonths($i);

                TeacherSalary::create([
                    'teacher_id' => $teacher->id,
                    'year'       => $date->year,
                    'month'      => $date->month,
                    'amount'     => rand(500, 1000),
                    'due_date'   => $date->endOfMonth(),
                    'is_paid'    => (bool) rand(0, 1),
                    'paid_at'    => rand(0, 1) ? $date->copy()->addDays(rand(0,5)) : null,
                    'method'     => Arr::random(['cash', 'bank', 'revolut']),
                    'notes'      => Arr::random([
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
