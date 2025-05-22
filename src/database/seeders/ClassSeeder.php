<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Carbon\Carbon;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker    = Faker::create();
        $lessonIds = DB::table('lessons')->pluck('id')->toArray();
        $teacgerIds = DB::table('teachers')->pluck('id')->toArray();
        $days     = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

        $rows = [];
        // let's generate 50 classes
        for ($i = 0; $i < 50; $i++) {
            // pick a random lesson
            $lessonId = $faker->randomElement($lessonIds);
            $teacherId = $faker->randomElement($teacgerIds);
            // pick a random day
            $day = $faker->randomElement($days);

            // generate a random start time (between 6am and 8pm)
            $start = Carbon::createFromTime(
                $faker->numberBetween(6, 20), 
                $faker->randomElement([0,15,30,45]),
                0
            );

            // duration between 30–90 minutes
            $duration = $faker->randomElement([30, 45, 60, 90]);
            $end = (clone $start)->addMinutes($duration);

            $rows[] = [
                'lesson_id'  => $lessonId,
                'teacher_id'  => $teacherId,
                'start_time' => $start->format('H:i:s'),
                'end_time'   => $end->format('H:i:s'),
                'day'        => $day,
                'capacity'   => $faker->numberBetween(5, 25),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // bulk insert
        DB::table('classes')->insert($rows);
    }
}
