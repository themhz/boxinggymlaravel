<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Models\Lesson as LessonModel;

class LessonControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Avoid MassAssignmentException in controller create/update during tests
        Model::unguard();
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    // ---------- Helpers -------------------------------------------------------

    /** Create a teacher row and return id (unique email by default). */
    private function makeTeacher(array $overrides = []): int
    {
        $defaults = [
            'first_name' => 'John',
            'last_name'  => 'Doe',
            // ensure uniqueness to avoid unique index collisions
            'email'      => 'teacher_'.uniqid('', true).'@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('teachers')->insertGetId(array_replace($defaults, $overrides));
    }

    /** Create a lesson row and return id. */
    private function makeLesson(array $overrides = []): int
    {
        $defaults = [
            'title'       => 'Boxing Basics',
            'description' => 'Intro',
            'level'       => 'all',
            'image'       => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];

        return DB::table('lessons')->insertGetId(array_replace($defaults, $overrides));
    }

    /** Attach teachers using the Eloquent relation so the correct pivot is used. */
    private function attachTeachersToLesson(int $lessonId, array $teacherIds): void
    {
        LessonModel::findOrFail($lessonId)->teachers()->attach($teacherIds);
    }

    // ---------- index() -------------------------------------------------------

    #[Test]
    public function it_index_returns_lessons_with_selected_fields(): void
    {
        $this->makeLesson(['title' => 'Kickboxing']);
        $this->makeLesson(['title' => 'Muay Thai']);

        $controller = app(\App\Http\Controllers\LessonController::class);
        $res = $controller->index();

        $data = $res->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('title', $data[0]);
        $this->assertArrayHasKey('description', $data[0]);
        $this->assertArrayHasKey('level', $data[0]);
        $this->assertArrayHasKey('image', $data[0]);
    }

    // ---------- show($id) -----------------------------------------------------

    #[Test]
    public function it_show_returns_a_single_lesson(): void
    {
        $lessonId = $this->makeLesson(['title' => 'Sparring 101']);

        $controller = app(\App\Http\Controllers\LessonController::class);
        $res = $controller->show($lessonId);

        $data = $res->getData(true);
        $this->assertEquals($lessonId, $data['id']);
        $this->assertEquals('Sparring 101', $data['title']);
    }

    #[Test]
    public function it_show_throws_404_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $controller = app(\App\Http\Controllers\LessonController::class);
        $controller->show(999999);
    }




    // ---------- store(Request) ------------------------------------------------

    #[Test]
    public function it_store_returns_422_when_validation_fails(): void
    {
        $controller = app(\App\Http\Controllers\LessonController::class);
        // Missing title & teacher_ids -> should return 422 JSON (no exception, controller handles it)
        $req = Request::create('/api/lessons', 'POST', [
            'description' => 'No title',
            'level'       => 'all',
        ]);

        $res = $controller->store($req);
        $this->assertSame(422, $res->status());
        $payload = $res->getData(true);
        $this->assertEquals('Validation failed', $payload['message']);
        $this->assertArrayHasKey('errors', $payload);
    }

    // ---------- update(Request, $id) ------------------------------------------   

    #[Test]
    public function it_update_returns_404_when_lesson_missing(): void
    {
        $controller = app(\App\Http\Controllers\LessonController::class);
        $req = Request::create('/api/lessons/999999', 'PUT', [
            'title' => 'Does not matter',
        ]);

        $res = $controller->update($req, 999999);
        $this->assertSame(404, $res->status());
        $this->assertEquals('Lesson not found', $res->getData(true)['message']);
    }

    #[Test]
    public function it_update_returns_422_on_validation_error(): void
    {
        $lessonId = $this->makeLesson();

        $controller = app(\App\Http\Controllers\LessonController::class);
        // teacher_ids includes a non-existent teacher -> should return 422 JSON
        $req = Request::create("/api/lessons/{$lessonId}", 'PUT', [
            'teacher_ids' => [123456789], // not existing
        ]);

        $res = $controller->update($req, $lessonId);
        $this->assertSame(422, $res->status());
        $payload = $res->getData(true);
        $this->assertEquals('Validation failed', $payload['message']);
        $this->assertArrayHasKey('errors', $payload);
    }

    // ---------- destroy($id) --------------------------------------------------

    #[Test]
    public function it_destroy_deletes_lesson_and_returns_1(): void
    {
        $lessonId = $this->makeLesson();

        $controller = app(\App\Http\Controllers\LessonController::class);
        $res = $controller->destroy($lessonId);

        $this->assertSame(200, $res->status());
        $this->assertEquals(1, $res->getData(true));
        $this->assertDatabaseMissing('lessons', ['id' => $lessonId]);
    }

    #[Test]
    public function it_destroy_returns_0_when_lesson_missing(): void
    {
        $controller = app(\App\Http\Controllers\LessonController::class);
        $res = $controller->destroy(999999);

        $this->assertSame(200, $res->status());
        $this->assertEquals(0, $res->getData(true));
    }
}
