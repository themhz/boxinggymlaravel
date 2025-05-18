<?php

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassModelFactory extends Factory
{
    protected $model = ClassModel::class;



    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'start_time' => $this->faker->time('H:i:s'),
            'end_time' => $this->faker->time('H:i:s'),
            'day' => $this->faker->dayOfWeek,
            'capacity' => $this->faker->numberBetween(5, 30),
        ];
    }
}
