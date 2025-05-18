<?php

namespace Database\Factories;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Teacher;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->word,
            'description' => $this->faker->paragraph,
            'teacher_id' => Teacher::factory(), // ← add this line
        ];
    }
}
