<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ClassModel;
use App\Models\Student;

class StudentClassesApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_students_of_a_class()
    {
        $class = ClassModel::factory()->create();
        $student = Student::factory()->create();
        $class->students()->attach($student->id);

        $this->getJson("/api/classes/{$class->id}/students")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $student->id]);
    }

    /** @test */
    public function it_adds_a_student_to_a_class()
    {
        $class = ClassModel::factory()->create();
        $student = Student::factory()->create();

        $this->postJson("/api/classes/{$class->id}/students", [
            'student_id' => $student->id
        ])
            ->assertStatus(201)
            ->assertJsonFragment(['id' => $student->id]);

        $this->assertDatabaseHas('class_student', [
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);
    }

    /** @test */
    public function it_updates_a_student_in_a_class()
    {
        $class = ClassModel::factory()->create();
        $student = Student::factory()->create();
        $class->students()->attach($student->id, ['status' => 'active']);

        $update = ['status' => 'inactive'];

        $this->putJson("/api/classes/{$class->id}/students/{$student->id}", $update)
            ->assertStatus(200)
            ->assertJsonFragment(['status' => 'inactive']);

        $this->assertDatabaseHas('class_student', [
            'class_id' => $class->id,
            'student_id' => $student->id,
            'status' => 'inactive',
        ]);
    }

    /** @test */
    public function it_deletes_a_student_from_a_class()
    {
        $class = ClassModel::factory()->create();
        $student = Student::factory()->create();
        $class->students()->attach($student->id);

        $this->deleteJson("/api/classes/{$class->id}/students/{$student->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Student removed from class']);

        $this->assertDatabaseMissing('class_student', [
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);
    }
}
