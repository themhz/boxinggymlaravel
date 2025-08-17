<?php

namespace Tests\Unit;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ClassModel;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\Student;

class ClassApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Teacher $t, Lesson $l): array
    {
        return [
            'teacher_id' => $t->id,
            'lesson_id'  => $l->id,
            'day'        => 'Monday',
            'time'       => '18:00',
            'capacity'   => 20,
        ];
    }

    #[Test]
    public function it_lists_classes()
    {
        ClassModel::factory()->count(3)->create();
        $this->getJson('/api/classes-schedule')->assertStatus(200);
    }

    #[Test]
    public function it_shows_a_class()
    {
        $class = ClassModel::factory()->create();
        $this->getJson("/api/classes/{$class->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $class->id]);
    }

    #[Test]
    public function it_creates_a_class()
    {
        $teacher = Teacher::factory()->create();
        $lesson  = Lesson::factory()->create();

        $this->postJson('/api/classes-schedule', $this->payload($teacher, $lesson))
             ->assertStatus(201)
             ->assertJsonFragment(['teacher_id' => $teacher->id, 'lesson_id' => $lesson->id]);
    }

    #[Test]
    public function it_updates_a_class()
    {
        $class = ClassModel::factory()->create(['day' => 'Monday', 'time' => '18:00']);

        $this->putJson("/api/classes-schedule/{$class->id}", [
                'day'  => 'Wednesday',
                'time' => '19:30',
            ])->assertStatus(200)
             ->assertJsonFragment(['day' => 'Wednesday', 'time' => '19:30']);
    }

    #[Test]
    public function it_deletes_a_class()
    {
        $class = ClassModel::factory()->create();

        $this->deleteJson("/api/classes-schedule/{$class->id}")
             ->assertStatus(200)
             ->assertJson(['message' => 'Class deleted']);
    }

    #[Test]
    public function it_adds_and_removes_student_from_class()
    {
        $class   = ClassModel::factory()->create();
        $student = Student::factory()->create();

        // add
        $this->postJson("/api/classes/{$class->id}/students", [
                'student_id' => $student->id,
            ])->assertStatus(201);

        // verify in show
        $this->getJson("/api/classes/{$class->id}/students")
             ->assertStatus(200)
             ->assertJsonFragment(['id' => $student->id]);

        // update student pivot (PUT/PATCH)
        $this->patchJson("/api/classes/{$class->id}/students/{$student->id}", [
                'status' => 'active'
            ])->assertStatus(200);

        // remove
        $this->deleteJson("/api/classes/{$class->id}/students/{$student->id}")
             ->assertStatus(200)
             ->assertJson(['message' => 'Student removed']);
    }

    #[Test]
    public function show_unknown_class_returns_404_json()
    {
        $this->getJson('/api/classes/999999')->assertStatus(404);
    }
}
