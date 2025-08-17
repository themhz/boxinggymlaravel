<?php

namespace Database\Factories;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'title'       => $this->faker->unique()->word(),   // <-- was 'name'
            'description' => $this->faker->sentence(),
            'image'       => null, // or a fake URL if column exists
        ];
    }
}
