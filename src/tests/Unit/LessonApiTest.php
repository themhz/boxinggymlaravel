<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Lesson;

class LessonApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_lessons()
    {
        Lesson::factory()->count(3)->create();
        $this->getJson('/api/lessons')->assertStatus(200);
    }

    /** @test */
    public function it_shows_a_lesson()
    {
        $lesson = Lesson::factory()->create();
        $this->getJson("/api/lessons/{$lesson->id}")
             ->assertStatus(200)
             ->assertJsonFragment(['id' => $lesson->id]);
    }

    /** @test */
    public function it_creates_a_lesson()
    {
        $payload = ['name' => 'Kickboxing']; // adjust fields if needed
        $this->postJson('/api/lessons', $payload)
             ->assertStatus(201)
             ->assertJsonFragment(['name' => 'Kickboxing']);
    }

    /** @test */
    public function it_updates_a_lesson()
    {
        $lesson = Lesson::factory()->create(['name' => 'Boxing']);
        $this->putJson("/api/lessons/{$lesson->id}", ['name' => 'Muay Thai'])
             ->assertStatus(200)
             ->assertJsonFragment(['name' => 'Muay Thai']);
    }

    /** @test */
    public function it_returns_lessons_with_teachers()
    {
        $this->getJson('/api/lessons-teachers')->assertStatus(200);
    }

    /** @test */
    public function unknown_lesson_returns_404()
    {
        $this->getJson('/api/lessons/999999')->assertStatus(404);
    }
}
