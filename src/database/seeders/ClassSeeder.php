<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $faker     = Faker::create();
        $lessonIds = DB::table('lessons')->pluck('id')->toArray();
        $days      = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

        if (empty($lessonIds)) {
            $this->command?->warn('⚠️ No lessons found. Seed lessons first.');
            return;
        }

        $rows = [];
        // generate 50 classes (no teacher_id here!)
        for ($i = 0; $i < 50; $i++) {
            $lessonId = $faker->randomElement($lessonIds);
            $day      = $faker->randomElement($days);

            // start between 06:00 and 20:45 on :00/:15/:30/:45
            $start = Carbon::createFromTime(
                $faker->numberBetween(6, 20),
                $faker->randomElement([0, 15, 30, 45]),
                0
            );

            // duration 30–90 minutes
            $duration = $faker->randomElement([30, 45, 60, 90]);
            $end      = (clone $start)->addMinutes($duration);

            $rows[] = [
                'lesson_id'  => $lessonId,
                'start_time' => $start->format('H:i:s'),
                'end_time'   => $end->format('H:i:s'),
                'day'        => $day,
                'capacity'   => $faker->numberBetween(5, 25),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('classes')->insert($rows);
        $this->command?->info('✅ ClassSeeder inserted '.count($rows).' classes (no teacher_id).');
    }
}
