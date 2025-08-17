<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Student;

class StudentApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_students()
    {
        $students = Student::factory()->count(3)->create();

        $this->getJson('/api/students')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $students[0]->id])
            ->assertJsonFragment(['id' => $students[1]->id]);
    }

    /** @test */
    public function it_shows_a_single_student()
    {
        $student = Student::factory()->create();

        $this->getJson("/api/students/{$student->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $student->id]);
    }

    /** @test */
    public function it_creates_a_student()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ];

        $this->postJson('/api/students', $data)
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'John Doe']);

        $this->assertDatabaseHas('students', ['email' => 'john@example.com']);
    }

    /** @test */
    public function it_updates_a_student()
    {
        $student = Student::factory()->create();

        $update = ['name' => 'Jane Doe'];

        $this->putJson("/api/students/{$student->id}", $update)
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Jane Doe']);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Jane Doe'
        ]);
    }

    /** @test */
    public function it_deletes_a_student()
    {
        $student = Student::factory()->create();

        $this->deleteJson("/api/students/{$student->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Student deleted']);

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }
}
