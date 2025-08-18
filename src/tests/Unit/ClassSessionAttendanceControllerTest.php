<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

use App\Http\Controllers\ClassSessionAttendanceController;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\User;

class ClassSessionAttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard();
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    // ---------------- Helpers ----------------

    private function makeUser(array $overrides = []): int
    {
        $defaults = [
            'name'       => 'User '.uniqid(),
            'email'      => 'user_'.uniqid('', true).'@example.com',
            'password'   => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('users')->insertGetId(array_replace($defaults, $overrides));
    }

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

    private function makeClass(array $overrides = []): int
    {
        $defaults = [
            'lesson_id'  => $this->makeLesson(),
            'start_time' => '18:00:00',
            'end_time'   => '19:00:00',
            'day'        => 'monday',
            'capacity'   => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('classes')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeSession(array $overrides = []): int
    {
        $defaults = [
            'class_id'   => $this->makeClass(),
            'date'       => '2025-01-10',
            'start_time' => '18:00:00',
            'end_time'   => '19:00:00',
            'notes'      => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('class_sessions')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeStudent(array $overrides = []): int
    {
        $defaults = [
            'name'       => 'Student '.uniqid(),
            'email'      => 'student_'.uniqid('', true).'@example.com',
            'phone'      => null,
            'dob'        => null,
            'image'      => null,
            'user_id'    => $this->makeUser(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('students')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeAttendance(array $overrides = []): int
    {
        $defaults = [
            'student_id' => $this->makeStudent(),
            'session_id' => $this->makeSession(),
            'status'     => 'present',
            'note'       => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('attendances')->insertGetId(array_replace($defaults, $overrides));
    }

    // ---------------- index() ----------------

    #[Test]
    public function it_index_lists_attendances_for_session(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession(['class_id' => $classId]);

        $s1 = $this->makeStudent();
        $s2 = $this->makeStudent();

        $a1 = $this->makeAttendance(['session_id' => $sessionId, 'student_id' => $s1, 'status' => 'present']);
        $a2 = $this->makeAttendance(['session_id' => $sessionId, 'student_id' => $s2, 'status' => 'late']);
        // another session's attendance (should not show)
        $this->makeAttendance();

        $controller = app(ClassSessionAttendanceController::class);
        $res = $controller->index($classId, $sessionId);

        $data = $res->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $ids = collect($data)->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$a1, $a2], $ids);
        $this->assertArrayHasKey('student', $data[0]); // relation loaded
    }

    #[Test]
    public function it_index_returns_404_when_session_not_in_class(): void
    {
        $classId   = $this->makeClass();
        $otherClassId = $this->makeClass();
        $sessionId = $this->makeSession(['class_id' => $otherClassId]); // does not belong to $classId

        $controller = app(ClassSessionAttendanceController::class);
        $this->expectException(HttpException::class);
        $controller->index($classId, $sessionId);
    }

    // ---------------- show() ----------------

    #[Test]
    public function it_show_returns_single_attendance_with_student(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession(['class_id' => $classId]);
        $attId     = $this->makeAttendance(['session_id' => $sessionId]);

        $controller = app(ClassSessionAttendanceController::class);
        $res = $controller->show($classId, $sessionId, $attId);

        $row = $res->getData(true);
        $this->assertSame($attId, $row['id']);
        $this->assertArrayHasKey('student', $row);
        $this->assertSame($sessionId, $row['session_id']);
    }

    #[Test]
    public function it_show_returns_404_when_session_not_in_class(): void
    {
        $classId   = $this->makeClass();
        $otherClassId = $this->makeClass();
        $sessionId = $this->makeSession(['class_id' => $otherClassId]);
        $attId     = $this->makeAttendance(['session_id' => $sessionId]);

        $controller = app(ClassSessionAttendanceController::class);
        $this->expectException(HttpException::class);
        $controller->show($classId, $sessionId, $attId);
    }

    // ---------------- store() ----------------

    #[Test]
    public function it_store_creates_attendance_for_session(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession(['class_id' => $classId]);
        $studentId = $this->makeStudent();

        $controller = app(ClassSessionAttendanceController::class);
        $req = Request::create("/api/classes/{$classId}/sessions/{$sessionId}/attendances", 'POST', [
            'student_id' => $studentId,
            'status'     => 'absent',
            'note'       => 'Sick',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res = $controller->store($classId, $sessionId, $req);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame($sessionId, $data['session_id']);
        $this->assertSame($studentId, $data['student_id']);
        $this->assertSame('absent', $data['status']);
        $this->assertSame('Sick', $data['note']);
        $this->assertArrayHasKey('student', $data);

        $this->assertDatabaseHas('attendances', [
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'status'     => 'absent',
            'note'       => 'Sick',
        ]);
    }

    #[Test]
    public function it_store_returns_422_on_validation_error(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession(['class_id' => $classId]);

        $controller = app(ClassSessionAttendanceController::class);
        // missing student_id
        $req = Request::create("/api/classes/{$classId}/sessions/{$sessionId}/attendances", 'POST', [
            'status' => 'present',
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->store($classId, $sessionId, $req);
    }

    // ---------------- update() ----------------

    #[Test]
    public function it_update_edits_attendance_fields(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession(['class_id' => $classId]);
        $attId     = $this->makeAttendance([
            'session_id' => $sessionId,
            'status'     => 'present',
            'note'       => 'ok',
        ]);

        $controller = app(ClassSessionAttendanceController::class);
        $req = Request::create("/api/classes/{$classId}/sessions/{$sessionId}/attendances/{$attId}", 'PATCH', [
            'status' => 'late',
            'note'   => 'traffic',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res = $controller->update($classId, $sessionId, $attId, $req);
        $data = $res->getData(true);

        $this->assertSame('late', $data['status']);
        $this->assertSame('traffic', $data['note']);

        $this->assertDatabaseHas('attendances', [
            'id'     => $attId,
            'status' => 'late',
            'note'   => 'traffic',
        ]);
    }

    #[Test]
    public function it_update_returns_404_when_session_not_in_class(): void
    {
        $classId   = $this->makeClass();
        $otherClassId = $this->makeClass();
        $sessionId = $this->makeSession(['class_id' => $otherClassId]);
        $attId     = $this->makeAttendance(['session_id' => $sessionId]);

        $controller = app(ClassSessionAttendanceController::class);
        $req = Request::create("/api/classes/{$classId}/sessions/{$sessionId}/attendances/{$attId}", 'PATCH', [
            'status' => 'late',
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(HttpException::class);
        $controller->update($classId, $sessionId, $attId, $req);
    }

    #[Test]
    public function it_update_returns_422_on_validation_error(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession(['class_id' => $classId]);
        $attId     = $this->makeAttendance(['session_id' => $sessionId]);

        $controller = app(ClassSessionAttendanceController::class);
        // invalid status
        $req = Request::create("/api/classes/{$classId}/sessions/{$sessionId}/attendances/{$attId}", 'PATCH', [
            'status' => 'banana',
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->update($classId, $sessionId, $attId, $req);
    }

    // ---------------- destroy() ----------------

    #[Test]
    public function it_destroy_deletes_attendance(): void
    {
        $classId   = $this->makeClass();
        $sessionId = $this->makeSession(['class_id' => $classId]);
        $attId     = $this->makeAttendance(['session_id' => $sessionId]);

        $controller = app(ClassSessionAttendanceController::class);
        $res = $controller->destroy($classId, $sessionId, $attId);

        $this->assertSame(200, $res->status());
        $this->assertTrue($res->getData(true)['deleted']);
        $this->assertDatabaseMissing('attendances', ['id' => $attId]);
    }

    #[Test]
    public function it_destroy_returns_404_when_session_not_in_class(): void
    {
        $classId   = $this->makeClass();
        $otherClassId = $this->makeClass();
        $sessionId = $this->makeSession(['class_id' => $otherClassId]);
        $attId     = $this->makeAttendance(['session_id' => $sessionId]);

        $controller = app(ClassSessionAttendanceController::class);
        $this->expectException(HttpException::class);
        $controller->destroy($classId, $sessionId, $attId);
    }
}
