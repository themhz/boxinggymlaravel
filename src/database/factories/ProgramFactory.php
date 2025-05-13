<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ClassType;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'class_type_id' => ClassType::factory(),
            'start_time' => '18:00:00',
            'end_time' => '19:30:00',
            'day' => $this->faker->dayOfWeek,
            'capacity' => 10,
        ];
    }

}
