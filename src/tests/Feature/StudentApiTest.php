<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class StudentApiTest extends TestCase
{
    use RefreshDatabase;
    protected $user;

    protected function authenticate()
    {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

    }

    #[Test]
    public function it_lists_students()
    {
        Student::factory()->count(3)->create();
        $response = $this->getJson('/api/students');
        $response->assertOk()->assertJsonCount(3);
    }

    #[Test]
    public function it_shows_a_single_student()
    {
        $student = Student::factory()->create();
        $response = $this->getJson("/api/students/{$student->id}");
        $response->assertOk()->assertJsonFragment(['id' => $student->id]);
    }

    #[Test]
    public function it_creates_a_student()
    {
        $this->authenticate();

        $data = ['name' => 'Test Student', 'email' => 'themhz@gmail.com','user_id' => $this->user->id,];
        $response = $this->postJson('/api/students', $data);

        $response->assertCreated()->assertJsonFragment(['name' => 'Test Student']);
        $this->assertDatabaseHas('students', $data);
    }

    #[Test]
    public function it_updates_a_student()
    {
        $this->authenticate();

        $student = Student::factory()->create();
        $updateData = ['name' => 'Updated Name'];

        $response = $this->putJson("/api/students/{$student->id}", $updateData);
        $response->assertOk()->assertJsonFragment($updateData);
        $this->assertDatabaseHas('students', $updateData);
    }

    #[Test]
    public function it_deletes_a_student()
    {
        $this->authenticate();

        $student = Student::factory()->create();
        $response = $this->deleteJson("/api/students/{$student->id}");
        $response->assertNoContent();
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }
}
