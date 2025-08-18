<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Http\Controllers\SessionExerciseStudentController;
use App\Models\SessionExerciseStudent;

class SessionExerciseStudentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard(); // avoid mass assignment issues for tests
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    // ---------------- Helpers ----------------

    private function req(string $method, string $uri, array $data = []): Request
    {
        $r = Request::create($uri, $method, $data);
        $r->headers->set('Accept', 'application/json');
        return $r;
    }

    private function tbl(): string
    {
        return (new SessionExerciseStudent())->getTable();
    }

    private function makeUser(array $overrides = []): int
    {
        $defaults = [
            'name'       => 'User '.uniqid(),
            'email'      => 'u'.uniqid().'@ex.com',
            'password'   => bcrypt('secret'),
            'role'       => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('users')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeStudent(array $overrides = []): int
    {
        $userId = $overrides['user_id'] ?? $this->makeUser();
        $defaults = [
            'user_id'    => $userId,
            'name'       => 'Student '.uniqid(),
            'email'      => 's'.uniqid().'@ex.com',
            'phone'      => null,
            'dob'        => null,
            'image'      => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('students')->insertGetId(array_replace($defaults, $overrides));
    }

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

    private function makeSession(int $classId = null, array $overrides = []): int
    {
        $classId = $classId ?? $this->makeClass();
        $defaults = [
            'class_id'   => $classId,
            'date'       => '2025-05-10',
            'start_time' => '08:00:00',
            'end_time'   => '09:00:00',
            'notes'      => 'Session',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('class_sessions')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeRow(array $overrides = []): int
    {
        // Avoid optional FKs (session_exercise_id, student_exercise_id) due to controller's exists typo.
        $payload = array_replace([
            'session_id'  => $overrides['session_id'] ?? $this->makeSession(),
            'student_id'  => $overrides['student_id'] ?? $this->makeStudent(),
            'performed_sets'             => $overrides['performed_sets'] ?? null,
            'performed_repetitions'      => $overrides['performed_repetitions'] ?? null,
            'performed_weight'           => $overrides['performed_weight'] ?? null,
            'performed_duration_seconds' => $overrides['performed_duration_seconds'] ?? null,
            'status'      => $overrides['status'] ?? 'completed',
            'created_at'  => now(),
            'updated_at'  => now(),
        ], $overrides);

        return SessionExerciseStudent::create($payload)->id;
    }

    // ---------------- index() ----------------

    #[Test]
    public function it_index_lists_and_filters(): void
    {
        $s1 = $this->makeSession();
        $s2 = $this->makeSession();
        $student1 = $this->makeStudent();
        $student2 = $this->makeStudent();

        // create 3 rows, 2 matching both filters
        $id1 = $this->makeRow(['session_id' => $s1, 'student_id' => $student1, 'status' => 'completed']);
        $id2 = $this->makeRow(['session_id' => $s1, 'student_id' => $student1, 'status' => 'partial']);
        $id3 = $this->makeRow(['session_id' => $s2, 'student_id' => $student2, 'status' => 'skipped']);

        $controller = app(SessionExerciseStudentController::class);
        $req = $this->req('GET', '/api/session-exercise-students', [
            'session_id' => $s1,
            'student_id' => $student1,
        ]);

        $res  = $controller->index($req);
        $data = $res->getData(true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertEqualsCanonicalizing([$id1, $id2], array_column($data, 'id'));
        $this->assertArrayHasKey('session', $data[0]);
        $this->assertArrayHasKey('student', $data[0]);
    }

    // ---------------- store() ----------------

    #[Test]
    public function it_store_creates_row_and_returns_201(): void
    {
        $sessionId = $this->makeSession();
        $studentId = $this->makeStudent();

        $controller = app(SessionExerciseStudentController::class);
        $req = $this->req('POST', '/api/session-exercise-students', [
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'performed_sets' => 3,
            'performed_repetitions' => 12,
            'performed_weight' => 45.5,
            'performed_duration_seconds' => 600,
            'status' => 'completed',
            // leaving optional FKs null
        ]);

        $res  = $controller->store($req);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame($sessionId, $data['session_id']);
        $this->assertSame($studentId, $data['student_id']);
        $this->assertSame(3, $data['performed_sets']);
        $this->assertSame(12, $data['performed_repetitions']);
        $this->assertEquals(45.5, $data['performed_weight']);
        $this->assertSame(600, $data['performed_duration_seconds']);
        $this->assertSame('completed', $data['status']);

        $this->assertArrayHasKey('session', $data);
        $this->assertArrayHasKey('student', $data);

        $this->assertDatabaseHas($this->tbl(), [
            'id' => $data['id'],
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function it_store_returns_422_on_validation_error(): void
    {
        $controller = app(SessionExerciseStudentController::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        // missing required fields
        $req = $this->req('POST', '/api/session-exercise-students', [
            'status' => 'completed',
        ]);
        $controller->store($req);
    }

    // ---------------- show() -----------------

    #[Test]
    public function it_show_returns_single_row_with_relations(): void
    {
        $id = $this->makeRow();

        $controller = app(SessionExerciseStudentController::class);
        $res  = $controller->show(SessionExerciseStudent::findOrFail($id));
        $data = $res->getData(true);

        $this->assertSame($id, $data['id']);
        $this->assertArrayHasKey('session', $data);
        $this->assertArrayHasKey('student', $data);
    }

    // ---------------- update() ---------------

    #[Test]
    public function it_update_edits_fields_and_returns_fresh(): void
    {
        $id = $this->makeRow([
            'performed_sets' => 1,
            'performed_repetitions' => 5,
            'performed_weight' => 10,
            'performed_duration_seconds' => 120,
            'status' => 'partial',
        ]);

        $controller = app(SessionExerciseStudentController::class);
        $req = $this->req('PATCH', "/api/session-exercise-students/{$id}", [
            'performed_sets' => 4,
            'performed_repetitions' => 20,
            'performed_weight' => 25.25,
            'performed_duration_seconds' => 900,
            'status' => 'completed',
        ]);

        $res  = $controller->update($req, SessionExerciseStudent::findOrFail($id));
        $data = $res->getData(true);

        $this->assertSame($id, $data['id']);
        $this->assertSame(4, $data['performed_sets']);
        $this->assertSame(20, $data['performed_repetitions']);
        $this->assertEquals(25.25, $data['performed_weight']);
        $this->assertSame(900, $data['performed_duration_seconds']);
        $this->assertSame('completed', $data['status']);

        $this->assertDatabaseHas($this->tbl(), [
            'id' => $id,
            'performed_sets' => 4,
            'performed_repetitions' => 20,
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function it_update_returns_422_on_invalid_status(): void
    {
        $id = $this->makeRow();

        $controller = app(SessionExerciseStudentController::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $req = $this->req('PATCH', "/api/session-exercise-students/{$id}", [
            'status' => 'invalid-status',
        ]);
        $controller->update($req, SessionExerciseStudent::findOrFail($id));
    }

    // ---------------- destroy() --------------

    #[Test]
    public function it_destroy_deletes_and_returns_true(): void
    {
        $id = $this->makeRow();

        $controller = app(SessionExerciseStudentController::class);
        $res  = $controller->destroy(SessionExerciseStudent::findOrFail($id));
        $data = $res->getData(true);

        $this->assertSame(['deleted' => true], $data);
        $this->assertDatabaseMissing($this->tbl(), ['id' => $id]);
    }
}
