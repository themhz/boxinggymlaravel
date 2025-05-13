<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Teacher;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassType>
 */
class ClassTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => $this->faker->word . ' Class',
            'description' => $this->faker->sentence(),
            'level' => $this->faker->randomElement(['Beginner', 'Intermediate', 'Advanced']),
            'image' => null,
            'teacher_id' => Teacher::factory(),
        ];
    }

}
