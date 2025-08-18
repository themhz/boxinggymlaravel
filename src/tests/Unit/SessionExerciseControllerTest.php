<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Http\Controllers\SessionExerciseController;
use App\Models\ClassSession;
use App\Models\SessionExercise;

class SessionExerciseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard(); // avoid mass-assignment issues in tests
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    // ---------------- Helpers ----------------

    private function makeLesson(array $overrides = []): int
    {
        $defaults = [
            'title'       => 'Boxing',
            'description' => 'Basics',
            'level'       => 'all',
            'image'       => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
        return DB::table('lessons')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeClass(array $overrides = []): int
    {
        $lessonId = $overrides['lesson_id'] ?? $this->makeLesson();
        $defaults = [
            'lesson_id'  => $lessonId,
            'start_time' => '08:00:00',
            'end_time'   => '09:00:00',
            'day'        => 'monday',
            'capacity'   => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('classes')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeSession(int $classId, array $overrides = []): int
    {
        $defaults = [
            'class_id'   => $classId,
            'date'       => '2025-05-10',
            'start_time' => '08:00:00',
            'end_time'   => '09:00:00',
            'notes'      => 'Morning',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('class_sessions')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeExercise(array $overrides = []): int
    {
        $defaults = [
            'name'          => 'Jump Rope',
            'description'   => 'Warmup',
            'exercise_type' => 'cardio',
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
        return DB::table('exercises')->insertGetId(array_replace($defaults, $overrides));
    }

    /**
     * Create through Eloquent so we respect the model's $table mapping.
     */
    private function makeSessionExercise(array $overrides = []): int
    {
        $payload = array_replace([
            'session_id'    => $overrides['session_id'] ?? $this->makeSession($this->makeClass()),
            'exercise_id'   => $overrides['exercise_id'] ?? $this->makeExercise(),
            'display_order' => $overrides['display_order'] ?? 1,
            'note'          => $overrides['note'] ?? null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ], $overrides);

        return SessionExercise::create($payload)->id;
    }

    private function req(string $method, string $uri, array $data = []): Request
    {
        $r = Request::create($uri, $method, $data);
        $r->headers->set('Accept', 'application/json');
        return $r;
    }

    private function sessionExerciseTable(): string
    {
        return (new SessionExercise())->getTable();
    }

    // --------------- index() -----------------

    #[Test]
    public function it_index_lists_session_exercises_ordered_by_display_order(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession($classId);

        $ex1 = $this->makeExercise(['name' => 'Shadow Boxing']);
        $ex2 = $this->makeExercise(['name' => 'Bag Work']);
        $ex3 = $this->makeExercise(['name' => 'Cool Down']);

        $this->makeSessionExercise(['session_id' => $sessionId, 'exercise_id' => $ex2, 'display_order' => 3]);
        $this->makeSessionExercise(['session_id' => $sessionId, 'exercise_id' => $ex1, 'display_order' => 1]);
        $this->makeSessionExercise(['session_id' => $sessionId, 'exercise_id' => $ex3, 'display_order' => 5]);

        $controller = app(SessionExerciseController::class);
        $res  = $controller->index($classId, $sessionId);
        $data = $res->getData(true);

        $this->assertIsArray($data);
        $this->assertCount(3, $data);
        $this->assertSame([1,3,5], array_column($data, 'display_order'));
        $this->assertArrayHasKey('exercise', $data[0]);
    }

    #[Test]
    public function it_index_404_when_session_not_in_class(): void
    {
        $class1   = $this->makeClass();
        $class2   = $this->makeClass(['day' => 'tuesday']);
        $session2 = $this->makeSession($class2);

        $controller = app(SessionExerciseController::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->index($class1, $session2);
    }

    // --------------- show() ------------------

    #[Test]
    public function it_show_returns_single_session_exercise_with_exercise(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession($classId);
        $id        = $this->makeSessionExercise(['session_id' => $sessionId]);

        $controller = app(SessionExerciseController::class);
        $res  = $controller->show($classId, $sessionId, $id);
        $data = $res->getData(true);

        $this->assertSame($id, $data['id']);
        $this->assertSame($sessionId, $data['session_id']);
        $this->assertArrayHasKey('exercise', $data);
    }

    #[Test]
    public function it_show_404_when_session_not_in_class(): void
    {
        $class1   = $this->makeClass();
        $class2   = $this->makeClass(['day' => 'wednesday']);
        $session2 = $this->makeSession($class2);
        $id       = $this->makeSessionExercise(['session_id' => $session2]);

        $controller = app(SessionExerciseController::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->show($class1, $session2, $id);
    }

    // --------------- store() -----------------

    #[Test]
    public function it_store_creates_session_exercise(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession($classId);
        $exercise  = $this->makeExercise(['name' => 'Sprints']);

        $controller = app(SessionExerciseController::class);
        $req = $this->req('POST', "/api/classes/{$classId}/sessions/{$sessionId}/exercises", [
            'exercise_id'   => $exercise,
            'display_order' => 2,
            'note'          => 'Hard effort',
        ]);

        $res  = $controller->store($classId, $sessionId, $req);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame($sessionId, $data['session_id']);
        $this->assertSame($exercise, $data['exercise_id']);
        $this->assertSame(2, $data['display_order']);
        $this->assertSame('Hard effort', $data['note']);
        $this->assertArrayHasKey('exercise', $data);

        $this->assertDatabaseHas($this->sessionExerciseTable(), [
            'session_id'    => $sessionId,
            'exercise_id'   => $exercise,
            'display_order' => 2,
            'note'          => 'Hard effort',
        ]);
    }

    #[Test]
    public function it_store_returns_422_on_validation_error(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession($classId);

        $controller = app(SessionExerciseController::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        // missing exercise_id
        $req = $this->req('POST', "/api/classes/{$classId}/sessions/{$sessionId}/exercises", [
            'display_order' => 1,
        ]);

        $controller->store($classId, $sessionId, $req);
    }

    #[Test]
    public function it_store_404_when_session_not_in_class(): void
    {
        $class1   = $this->makeClass();
        $class2   = $this->makeClass(['day' => 'friday']);
        $session2 = $this->makeSession($class2);
        $exercise = $this->makeExercise();

        $controller = app(SessionExerciseController::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $req = $this->req('POST', "/api/classes/{$class1}/sessions/{$session2}/exercises", [
            'exercise_id' => $exercise,
        ]);

        $controller->store($class1, $session2, $req);
    }

    // --------------- update() ----------------

    #[Test]
    public function it_update_edits_fields(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession($classId);
        $id        = $this->makeSessionExercise(['session_id' => $sessionId, 'display_order' => 1, 'note' => 'old']);
        $newEx     = $this->makeExercise(['name' => 'Heavy Bag']);

        $controller = app(SessionExerciseController::class);
        $req = $this->req('PATCH', "/api/classes/{$classId}/sessions/{$sessionId}/exercises/{$id}", [
            'exercise_id'   => $newEx,
            'display_order' => 4,
            'note'          => 'updated',
        ]);

        $res  = $controller->update($classId, $sessionId, $id, $req);
        $data = $res->getData(true);

        $this->assertSame($id, $data['id']);
        $this->assertSame($newEx, $data['exercise_id']);
        $this->assertSame(4, $data['display_order']);
        $this->assertSame('updated', $data['note']);
        $this->assertArrayHasKey('exercise', $data);

        $this->assertDatabaseHas($this->sessionExerciseTable(), [
            'id'            => $id,
            'exercise_id'   => $newEx,
            'display_order' => 4,
            'note'          => 'updated',
        ]);
    }

    #[Test]
    public function it_update_returns_422_on_validation_error(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession($classId);
        $id        = $this->makeSessionExercise(['session_id' => $sessionId]);

        $controller = app(SessionExerciseController::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        // invalid display_order
        $req = $this->req('PATCH', "/api/classes/{$classId}/sessions/{$sessionId}/exercises/{$id}", [
            'display_order' => 0,
        ]);

        $controller->update($classId, $sessionId, $id, $req);
    }

    #[Test]
    public function it_update_404_when_session_not_in_class(): void
    {
        $class1   = $this->makeClass();
        $class2   = $this->makeClass(['day' => 'thursday']);
        $session2 = $this->makeSession($class2);
        $id       = $this->makeSessionExercise(['session_id' => $session2]);

        $controller = app(SessionExerciseController::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $req = $this->req('PATCH', "/api/classes/{$class1}/sessions/{$session2}/exercises/{$id}", [
            'note' => 'x',
        ]);

        $controller->update($class1, $session2, $id, $req);
    }

    // --------------- destroy() ---------------

    #[Test]
    public function it_destroy_deletes_row_and_returns_true(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession($classId);
        $id        = $this->makeSessionExercise(['session_id' => $sessionId]);

        $controller = app(SessionExerciseController::class);
        $res  = $controller->destroy($classId, $sessionId, $id);
        $data = $res->getData(true);

        $this->assertSame(['deleted' => true], $data);
        $this->assertDatabaseMissing($this->sessionExerciseTable(), ['id' => $id]);
    }

    #[Test]
    public function it_destroy_404_when_session_not_in_class(): void
    {
        $class1   = $this->makeClass();
        $class2   = $this->makeClass(['day' => 'sunday']);
        $session2 = $this->makeSession($class2);
        $id       = $this->makeSessionExercise(['session_id' => $session2]);

        $controller = app(SessionExerciseController::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $controller->destroy($class1, $session2, $id);
    }
}
