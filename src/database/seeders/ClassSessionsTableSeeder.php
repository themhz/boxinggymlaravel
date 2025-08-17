<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassSession;
use Carbon\Carbon;

class ClassSessionsTableSeeder extends Seeder
{
    public function run(): void
    {
        // Create 20 sessions spread over different classes & days
        for ($i = 1; $i <= 20; $i++) {
            ClassSession::create([
                'class_id'   => rand(1, 5), // assuming you have at least 5 classes
                'date'       => Carbon::now()->addDays(rand(1, 30)), // within the next 30 days
                'start_time' => Carbon::createFromTime(rand(8, 20), [0, 30][rand(0, 1)]), // random hour between 8:00 and 20:30
                'end_time'   => Carbon::createFromTime(rand(8, 20), [0, 30][rand(0, 1)]), // random end time
                'notes'      => 'Auto-generated session ' . $i,
            ]);
        }
    }
}
