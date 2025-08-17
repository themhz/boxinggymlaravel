<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ClassModel;
use App\Models\Student;

class ClassStudentsApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_students_in_a_class()
    {
        $class = ClassModel::factory()->create();
        $student = Student::factory()->create();
        $class->students()->attach($student->id);

        $this->getJson("/api/classes/{$class->id}/students")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $student->id]);
    }

    /** @test */
    public function it_can_add_a_student_to_a_class()
    {
        $class = ClassModel::factory()->create();
        $student = Student::factory()->create();

        $this->postJson("/api/classes/{$class->id}/students", [
            'student_id' => $student->id,
        ])
        ->assertStatus(201)
        ->assertJsonFragment(['student_id' => $student->id]);

        $this->assertDatabaseHas('class_student', [
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);
    }

    /** @test */
    public function it_can_update_a_student_in_a_class()
    {
        $class = ClassModel::factory()->create();
        $student = Student::factory()->create();
        $class->students()->attach($student->id);

        $this->putJson("/api/classes/{$class->id}/students/{$student->id}", [
            'status' => 'active',
        ])
        ->assertStatus(200)
        ->assertJsonFragment(['status' => 'active']);
    }

    /** @test */
    public function it_can_remove_a_student_from_a_class()
    {
        $class = ClassModel::factory()->create();
        $student = Student::factory()->create();
        $class->students()->attach($student->id);

        $this->deleteJson("/api/classes/{$class->id}/students/{$student->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Student removed']);

        $this->assertDatabaseMissing('class_student', [
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);
    }
}
