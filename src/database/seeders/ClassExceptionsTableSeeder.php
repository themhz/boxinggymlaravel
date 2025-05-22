<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassException;
use Carbon\Carbon;

class ClassExceptionsTableSeeder extends Seeder
{
    public function run()
    {
        // Example: Cancel Class #1 on New Year's Day
        ClassException::create([
            'class_id'            => 1,
            'exception_date'      => Carbon::create(2026, 1, 1),
            'is_cancelled'        => true,
            'override_start_time' => null,
            'override_end_time'   => null,
        ]);

        // Example: Shorten Class #2 on July 4th (override times)
        ClassException::create([
            'class_id'            => 2,
            'exception_date'      => Carbon::create(2025, 7, 4),
            'is_cancelled'        => false,
            'override_start_time' => '10:00:00',
            'override_end_time'   => '11:00:00',
        ]);
    }
}
