<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Teacher;

class TeacherApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_teachers()
    {
        Teacher::factory()->count(2)->create();

        $this->getJson('/api/teachers')
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** @test */
    public function it_creates_a_teacher()
    {
        $payload = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
        ];

        $this->postJson('/api/teachers', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['email' => 'john.doe@example.com']);
    }

    /** @test */
    public function it_shows_a_single_teacher()
    {
        $teacher = Teacher::factory()->create();

        $this->getJson("/api/teachers/{$teacher->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $teacher->id]);
    }

    /** @test */
    public function it_updates_a_teacher()
    {
        $teacher = Teacher::factory()->create();

        $update = ['first_name' => 'UpdatedName'];

        $this->patchJson("/api/teachers/{$teacher->id}", $update)
            ->assertStatus(200)
            ->assertJsonFragment(['first_name' => 'UpdatedName']);
    }

    /** @test */
    public function it_deletes_a_teacher()
    {
        $teacher = Teacher::factory()->create();

        $this->deleteJson("/api/teachers/{$teacher->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Teacher deleted']);
    }
}
