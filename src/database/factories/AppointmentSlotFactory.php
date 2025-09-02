<?php

namespace Database\Factories;

use App\Models\AppointmentSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentSlotFactory extends Factory
{
    protected $model = AppointmentSlot::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 day', '+1 week');
        return [
            'start_time' => $start,
            'end_time'   => (clone $start)->modify('+1 hour'),
            'capacity'   => 1,
            'created_by' => null,
        ];
    }
}
