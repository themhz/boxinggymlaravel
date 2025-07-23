<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassException;
use Carbon\Carbon;
use App\Models\ClassModel; 
class ClassExceptionSeeder extends Seeder
{
    public function run()
    {
        // Make sure we have at least 1 class
        $class = ClassModel::first();

        if (!$class) {
            $class = ClassModel::factory()->create(); // or create manually
        }

        ClassException::create([
            'class_id' => $class->id,
            'exception_date' => now()->addDays(3)->toDateString(),
            'is_cancelled' => true,
            'reason' => 'Instructor unavailable',
        ]);

        ClassException::create([
            'class_id' => $class->id,
            'exception_date' => now()->addDays(7)->toDateString(),
            'is_cancelled' => false,
            'override_start_time' => '14:00:00',
            'override_end_time' => '15:30:00',
            'reason' => 'Time rescheduled for tournament prep',
        ]);
    }
}
