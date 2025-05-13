<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Team;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'specialty' => $this->faker->randomElement(['Boxing', 'Muay Thai', 'BJJ']),
            'bio' => $this->faker->paragraph,
            'photo' => null,
            'team_id' => Team::factory(),
        ];
    }

}
