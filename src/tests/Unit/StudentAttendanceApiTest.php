<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Student;

class StudentAttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_attendance_for_a_student()
    {
        $student = Student::factory()->create();

        // Seed via API
        $this->postJson("/api/students/{$student->id}/attendance", [
            'date'   => now()->toDateString(),
            'status' => 'present',
        ]);

        $this->getJson("/api/students/{$student->id}/attendance")
            ->assertStatus(200)
            ->assertJsonFragment(['status' => 'present']);
    }

    /** @test */
    public function it_creates_an_attendance_record()
    {
        $student = Student::factory()->create();

        $payload = [
            'date'   => now()->toDateString(),
            'status' => 'present',
        ];

        $this->postJson("/api/students/{$student->id}/attendance", $payload)
            ->assertStatus(201)
            ->assertJsonFragment($payload);
    }

    /** @test */
    public function it_updates_an_attendance_record()
    {
        $student = Student::factory()->create();

        // First create via API
        $response = $this->postJson("/api/students/{$student->id}/attendance", [
            'date'   => now()->toDateString(),
            'status' => 'present',
        ])->json();

        $attendanceId = $response['id'] ?? null;

        $this->patchJson("/api/students/{$student->id}/attendance/{$attendanceId}", [
            'status' => 'absent',
        ])->assertStatus(200)
          ->assertJsonFragment(['status' => 'absent']);
    }

    /** @test */
    public function it_shows_a_single_attendance_record()
    {
        $student = Student::factory()->create();

        $response = $this->postJson("/api/students/{$student->id}/attendance", [
            'date'   => now()->toDateString(),
            'status' => 'present',
        ])->json();

        $attendanceId = $response['id'] ?? null;

        $this->getJson("/api/students/{$student->id}/attendance/{$attendanceId}")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $attendanceId]);
    }

    /** @test */
    public function it_deletes_an_attendance_record()
    {
        $student = Student::factory()->create();

        $response = $this->postJson("/api/students/{$student->id}/attendance", [
            'date'   => now()->toDateString(),
            'status' => 'present',
        ])->json();

        $attendanceId = $response['id'] ?? null;

        $this->deleteJson("/api/students/{$student->id}/attendance/{$attendanceId}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Attendance deleted']);
    }
}
