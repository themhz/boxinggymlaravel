<?php

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class ClassModelFactory extends Factory
{
    protected $model = ClassModel::class;

    public function definition(): array
    {
        $start = $this->faker->time('H:i:s');
        $end   = Carbon::createFromFormat('H:i:s', $start)->addMinutes(60)->format('H:i:s');

        return [
            'lesson_id'  => Lesson::factory(),
            'day'        => $this->faker->randomElement(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']),
            'start_time' => $start,
            'end_time'   => $end,
            'capacity'   => $this->faker->numberBetween(5, 30),
        ];
    }
}
