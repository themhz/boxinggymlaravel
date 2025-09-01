<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Student;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'         => $this->faker->name,
            'email'        => $this->faker->optional()->safeEmail,
            'phone'        => $this->faker->optional()->phoneNumber,
            'scheduled_at' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'notes'        => $this->faker->optional()->sentence,
            'status'       => $this->faker->randomElement(['pending', 'confirmed', 'cancelled']),
        ];
    }
}
