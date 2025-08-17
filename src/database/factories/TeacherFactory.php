<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name'  => $this->faker->lastName(),
            'email'      => $this->faker->unique()->safeEmail(), // REQUIRED in your schema
            // keep only columns that exist in your teachers table:
            // 'bio'   => $this->faker->sentence(),
            // 'photo' => null,
        ];
    }
}
