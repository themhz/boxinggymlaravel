<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Lesson;

class TeacherClassApiTest extends TestCase
{
    use RefreshDatabase;

    protected Teacher $teacher;
    protected Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = Teacher::factory()->create();
        $this->lesson = Lesson::factory()->create();
    }

    /** @test */
    public function it_lists_a_teachers_classes()
    {
        ClassModel::factory()->count(2)->create([
            'teacher_id' => $this->teacher->id,
            'lesson_id' => $this->lesson->id,
        ]);

        $this->getJson("/api/teachers/{$this->teacher->id}/classes")
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** @test */
    public function it_creates_a_class_for_a_teacher()
    {
        $payload = [
            'lesson_id' => $this->lesson->id,
            'name' => 'Kickboxing Basics',
            'schedule' => 'Monday 18:00',
        ];

        $this->postJson("/api/teachers/{$this->teacher->id}/classes", $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'Kickboxing Basics']);
    }

    /** @test */
    public function it_shows_a_class_of_a_teacher()
    {
        $class = ClassModel::factory()->create([
            'teacher_id' => $this->teacher->id,
            'lesson_id' => $this->lesson->id,
        ]);

        $this->getJson("/api/teachers/{$this->teacher->id}/classes/{$class->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $class->id]);
    }

    /** @test */
    public function it_updates_a_class_of_a_teacher()
    {
        $class = ClassModel::factory()->create([
            'teacher_id' => $this->teacher->id,
            'lesson_id' => $this->lesson->id,
        ]);

        $update = ['name' => 'Updated Class'];

        $this->patchJson("/api/teachers/{$this->teacher->id}/classes/{$class->id}", $update)
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Class']);
    }

    /** @test */
    public function it_deletes_a_class_of_a_teacher()
    {
        $class = ClassModel::factory()->create([
            'teacher_id' => $this->teacher->id,
            'lesson_id' => $this->lesson->id,
        ]);

        $this->deleteJson("/api/teachers/{$this->teacher->id}/classes/{$class->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Class deleted']);
    }
}
