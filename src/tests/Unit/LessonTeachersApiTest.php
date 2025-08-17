<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Lesson;
use App\Models\Teacher;

class LessonTeachersApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_lessons_with_teachers()
    {
        $lesson = Lesson::factory()->create(['name' => 'Boxing']);
        $teacher = Teacher::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        // Assuming you have a pivot/relationship (lesson_teacher, or lesson->teachers())
        $lesson->teachers()->attach($teacher->id);

        $this->getJson('/api/lessons-teachers')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Boxing'])
            ->assertJsonFragment(['first_name' => 'John']);
    }

    /** @test */
    public function it_returns_empty_if_no_lessons()
    {
        $this->getJson('/api/lessons-teachers')
            ->assertStatus(200)
            ->assertJsonCount(0);
    }
}
