<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LessonApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    #[Test]
    public function it_lists_lessons()
    {
        Lesson::factory()->count(3)->create();
        $response = $this->getJson('/api/lessons');
        $response->assertOk()->assertJsonCount(3);
    }

    #[Test]
    public function it_shows_a_single_lesson()
    {
        $lesson = Lesson::factory()->create();
        $response = $this->getJson("/api/lessons/{$lesson->id}");
        $response->assertOk()->assertJsonFragment(['id' => $lesson->id]);
    }

    #[Test]
    public function it_creates_a_lesson()
    {
        $this->authenticate();
        $teacher = Teacher::factory()->create();

        $data = [
            'title' => 'Muay Thai',
            'description' => 'A striking martial art from Thailand.',
            'teacher_id' => $teacher->id,
        ];

        $response = $this->postJson('/api/lessons', $data);
        $response->assertCreated()->assertJsonFragment(['title' => 'Muay Thai']);
        $this->assertDatabaseHas('lessons', $data);
    }

    #[Test]
    public function it_updates_a_lesson()
    {
        $this->authenticate();
        $lesson = Lesson::factory()->create();

        $data = [
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/lessons/{$lesson->id}", $data);
        $response->assertOk()->assertJsonFragment(['description' => 'Updated description']);
        $this->assertDatabaseHas('lessons', array_merge(['id' => $lesson->id], $data));
    }

    #[Test]
    public function it_deletes_a_lesson()
    {
        $this->authenticate();
        $lesson = Lesson::factory()->create();

        $response = $this->deleteJson("/api/lessons/{$lesson->id}");
        $response->assertNoContent();
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }
}
