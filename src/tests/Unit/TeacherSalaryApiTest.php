<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Teacher;
use App\Models\TeacherSalary;

class TeacherSalaryApiTest extends TestCase
{
    use RefreshDatabase;

    protected Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = Teacher::factory()->create();
    }

    /** @test */
    public function it_lists_teacher_salaries()
    {
        TeacherSalary::factory()->count(2)->create([
            'teacher_id' => $this->teacher->id,
        ]);

        $this->getJson("/api/teachers/{$this->teacher->id}/salaries")
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** @test */
    public function it_creates_a_salary_for_a_teacher()
    {
        $payload = [
            'year' => 2025,
            'month' => 8,
            'amount' => 1200.50,
            'due_date' => now()->toDateString(),
            'is_paid' => false,
            'method' => 'bank transfer',
            'notes' => 'First payment',
        ];

        $this->postJson("/api/teachers/{$this->teacher->id}/salaries", $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['amount' => 1200.50]);
    }

    /** @test */
    public function it_shows_a_teacher_salary()
    {
        $salary = TeacherSalary::factory()->create([
            'teacher_id' => $this->teacher->id,
        ]);

        $this->getJson("/api/teachers/{$this->teacher->id}/salaries/{$salary->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $salary->id]);
    }

    /** @test */
    public function it_updates_a_teacher_salary()
    {
        $salary = TeacherSalary::factory()->create([
            'teacher_id' => $this->teacher->id,
            'amount' => 1000,
        ]);

        $update = ['amount' => 1500, 'is_paid' => true];

        $this->patchJson("/api/teachers/{$this->teacher->id}/salaries/{$salary->id}", $update)
            ->assertStatus(200)
            ->assertJsonFragment(['amount' => 1500, 'is_paid' => true]);
    }

    /** @test */
    public function it_deletes_a_teacher_salary()
    {
        $salary = TeacherSalary::factory()->create([
            'teacher_id' => $this->teacher->id,
        ]);

        $this->deleteJson("/api/teachers/{$this->teacher->id}/salaries/{$salary->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Teacher salary deleted']);
    }
}
