<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Student;

class StudentExerciseApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_exercises_for_a_student()
    {
        $student = Student::factory()->create();

        // Seed via API
        $this->postJson("/api/students/{$student->id}/exercises", [
            'name' => 'Push Ups',
            'reps' => 20,
        ]);

        $this->getJson("/api/students/{$student->id}/exercises")
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Push Ups']);
    }

    /** @test */
    public function it_creates_a_student_exercise()
    {
        $student = Student::factory()->create();

        $payload = [
            'name' => 'Squats',
            'reps' => 15,
        ];

        $this->postJson("/api/students/{$student->id}/exercises", $payload)
            ->assertStatus(201)
            ->assertJsonFragment($payload);
    }

    /** @test */
    public function it_updates_a_student_exercise()
    {
        $student = Student::factory()->create();

        $response = $this->postJson("/api/students/{$student->id}/exercises", [
            'name' => 'Pull Ups',
            'reps' => 5,
        ])->json();

        $exerciseId = $response['id'] ?? null;

        $this->patchJson("/api/students/{$student->id}/exercises/{$exerciseId}", [
            'reps' => 10,
        ])->assertStatus(200)
          ->assertJsonFragment(['reps' => 10]);
    }

    /** @test */
    public function it_deletes_a_student_exercise()
    {
        $student = Student::factory()->create();

        $response = $this->postJson("/api/students/{$student->id}/exercises", [
            'name' => 'Lunges',
            'reps' => 12,
        ])->json();

        $exerciseId = $response['id'] ?? null;

        $this->deleteJson("/api/students/{$student->id}/exercises/{$exerciseId}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Exercise deleted']);
    }
}
