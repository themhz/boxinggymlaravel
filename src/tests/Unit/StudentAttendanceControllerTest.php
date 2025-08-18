<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\StudentAttendanceController;
use App\Models\User;
use App\Models\Student;
use App\Models\Attendance;

class StudentAttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard(); // allow mass assignment in tests
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

    private function makeStudent(array $overrides = []): int
    {
        $defaults = [
            'name'       => 'Student '.uniqid(),
            'email'      => 'student_'.uniqid('', true).'@example.com',
            'user_id'    => $this->makeUser(),
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

    private function makeClassSession(array $overrides = []): int
    {
        // class_sessions requires: class_id, date; we also provide start/end_time for safety
        $defaults = [
            'class_id'   => $this->makeClass(),
            'date'       => '2025-01-10',
            'start_time' => '18:00:00',
            'end_time'   => '19:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('class_sessions')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeAttendance(array $overrides = []): int
    {
        $defaults = [
            'student_id' => $this->makeStudent(),
            'session_id' => $this->makeClassSession(),
            'status'     => 'present',
            'note'       => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('attendances')->insertGetId(array_replace($defaults, $overrides));
    }

    // ---------------- index() ----------------

    #[Test]
    public function it_index_lists_attendance_for_student_with_filters_and_ordering(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $other   = Student::findOrFail($this->makeStudent());

        $classA  = $this->makeClass();
        $classB  = $this->makeClass();

        $sessionOldA = $this->makeClassSession(['class_id' => $classA, 'date' => '2025-01-01', 'created_at' => now()->subDay()]);
        $sessionNewA = $this->makeClassSession(['class_id' => $classA, 'date' => '2025-02-01', 'created_at' => now()]);
        $sessionB    = $this->makeClassSession(['class_id' => $classB, 'date' => '2025-03-15']);

        // student attendances (two in classA, one in classB)
        $a1 = $this->makeAttendance(['student_id' => $student->id, 'session_id' => $sessionOldA, 'status' => 'present', 'created_at' => now()->subDay()]);
        $a2 = $this->makeAttendance(['student_id' => $student->id, 'session_id' => $sessionNewA, 'status' => 'late',    'created_at' => now()]);
        $a3 = $this->makeAttendance(['student_id' => $student->id, 'session_id' => $sessionB,    'status' => 'absent']);

        // other student's attendance should not appear
        $this->makeAttendance(['student_id' => $other->id, 'session_id' => $sessionB]);

        $controller = app(StudentAttendanceController::class);

        // no filters: should return 3, ordered desc by created_at (a2 newest)
        $res = $controller->index(Request::create('', 'GET'), $student);
        $data = $res->getData(true);
        $this->assertCount(3, $data);
        $this->assertSame($a2, $data[0]['id']);

        // by class_id (classA): two records
        $reqClass = Request::create('', 'GET', ['class_id' => $classA]);
        $resClass = $controller->index($reqClass, $student);
        $this->assertCount(2, $resClass->getData(true));

        // by status = absent: one record
        $reqStatus = Request::create('', 'GET', ['status' => 'absent']);
        $resStatus = $controller->index($reqStatus, $student);
        $this->assertCount(1, $resStatus->getData(true));

        // date range from 2025-02-01 to 2025-03-31 -> should match a2 and a3 (2 items)
        $reqRange = Request::create('', 'GET', ['from' => '2025-02-01', 'to' => '2025-03-31']);
        $resRange = $controller->index($reqRange, $student);
        $this->assertCount(2, $resRange->getData(true));

        // pagination branch: per_page=2 → JSON paginator with 'data'
        $reqPage = Request::create('', 'GET', ['per_page' => 2]);
        $resPage = $controller->index($reqPage, $student);
        $payload = $resPage->getData(true);
        $this->assertArrayHasKey('data', $payload);
        $this->assertCount(2, $payload['data']);
    }

    // ---------------- show() ----------------

    #[Test]
    public function it_show_returns_attendance_with_relations_when_owned(): void
    {
        $studentId = $this->makeStudent();
        $sessionId = $this->makeClassSession(['date' => '2025-04-01']);
        $attId     = $this->makeAttendance(['student_id' => $studentId, 'session_id' => $sessionId, 'status' => 'present']);

        $student    = Student::findOrFail($studentId);
        $attendance = Attendance::findOrFail($attId);

        $controller = app(StudentAttendanceController::class);
        $res = $controller->show($student, $attendance);

        $data = $res->getData(true);
        $this->assertSame($attId, $data['id']);
        // ensure nested relations exist: session -> class -> lesson
        $this->assertArrayHasKey('session', $data);
        $this->assertArrayHasKey('class', $data['session']);
        $this->assertArrayHasKey('lesson', $data['session']['class']);
    }

    #[Test]
    public function it_show_returns_404_when_not_owned_by_student(): void
    {
        $owner   = Student::findOrFail($this->makeStudent());
        $other   = Student::findOrFail($this->makeStudent());
        $attId   = $this->makeAttendance(['student_id' => $owner->id]);

        $attendance = Attendance::findOrFail($attId);

        $controller = app(StudentAttendanceController::class);
        $res = $controller->show($other, $attendance);

        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
    }

    // ---------------- store() ----------------

    #[Test]
    public function it_store_creates_attendance_and_loads_relations(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $session = $this->makeClassSession(['date' => '2025-05-01']);

        $controller = app(StudentAttendanceController::class);
        $req = Request::create("/api/students/{$student->id}/attendance", 'POST', [
            'session_id' => $session,
            'status'     => 'present',
            'note'       => 'On time',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->store($req, $student);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('Attendance created', $data['message']);
        $this->assertSame('present', $data['attendance']['status']);
        $this->assertArrayHasKey('session', $data['attendance']);
        $this->assertArrayHasKey('class', $data['attendance']['session']);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'session_id' => $session,
            'status'     => 'present',
        ]);
    }

    #[Test]
    public function it_store_enforces_unique_session_per_student(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $session = $this->makeClassSession();

        // pre-existing record for (student, session)
        $this->makeAttendance(['student_id' => $student->id, 'session_id' => $session]);

        $controller = app(StudentAttendanceController::class);
        $req = Request::create("/api/students/{$student->id}/attendance", 'POST', [
            'session_id' => $session,
            'status'     => 'present',
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->store($req, $student);
    }

    // ---------------- update() ----------------

    #[Test]
    public function it_update_updates_when_owned_and_valid(): void
    {
        $student  = Student::findOrFail($this->makeStudent());
        $sessionA = $this->makeClassSession(['date' => '2025-06-01']);
        $sessionB = $this->makeClassSession(['date' => '2025-06-02']);

        $attId = $this->makeAttendance([
            'student_id' => $student->id,
            'session_id' => $sessionA,
            'status'     => 'late',
            'note'       => 'traffic',
        ]);

        $attendance = Attendance::findOrFail($attId);

        $controller = app(StudentAttendanceController::class);
        $req = Request::create("/api/students/{$student->id}/attendance/{$attId}", 'PATCH', [
            'session_id' => $sessionB,
            'status'     => 'present',
            'note'       => 'updated',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->update($req, $student, $attendance);
        $data = $res->getData(true);

        $this->assertSame('Attendance updated successfully.', $data['message']);
        $this->assertSame('present', $data['attendance']['status']);
        $this->assertSame('updated', $data['attendance']['note']);

        $this->assertDatabaseHas('attendances', [
            'id'         => $attId,
            'session_id' => $sessionB,
            'status'     => 'present',
            'note'       => 'updated',
        ]);
    }

    #[Test]
    public function it_update_returns_404_when_not_owned_by_student(): void
    {
        $owner     = Student::findOrFail($this->makeStudent());
        $other     = Student::findOrFail($this->makeStudent());
        $attId     = $this->makeAttendance(['student_id' => $owner->id]);
        $attendance= Attendance::findOrFail($attId);

        $controller = app(StudentAttendanceController::class);
        $req = Request::create("/api/students/{$other->id}/attendance/{$attId}", 'PATCH', [
            'session_id' => $this->makeClassSession(),
            'status'     => 'present',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res = $controller->update($req, $other, $attendance);
        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
    }

    #[Test]
    public function it_update_enforces_unique_session_per_student(): void
    {
        $student  = Student::findOrFail($this->makeStudent());
        $sessionA = $this->makeClassSession();
        $sessionB = $this->makeClassSession();

        // student already has attendance for sessionA
        $existingId = $this->makeAttendance(['student_id' => $student->id, 'session_id' => $sessionA]);

        // this record currently points to sessionB
        $targetId   = $this->makeAttendance(['student_id' => $student->id, 'session_id' => $sessionB, 'status' => 'absent']);
        $target     = Attendance::findOrFail($targetId);

        $controller = app(StudentAttendanceController::class);
        // try to change target to sessionA -> violates unique(student_id, session_id)
        $req = Request::create("/api/students/{$student->id}/attendance/{$targetId}", 'PATCH', [
            'session_id' => $sessionA,
            'status'     => 'present',
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->update($req, $student, $target);
    }

    // ---------------- destroy() --------------

    #[Test]
    public function it_destroy_deletes_when_owned(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $attId   = $this->makeAttendance(['student_id' => $student->id]);

        $attendance = Attendance::findOrFail($attId);

        $controller = app(StudentAttendanceController::class);
        $res  = $controller->destroy($student, $attendance);
        $data = $res->getData(true);

        $this->assertSame('Attendance deleted', $data['message']);
        $this->assertDatabaseMissing('attendances', ['id' => $attId]);
    }

    #[Test]
    public function it_destroy_returns_404_when_not_owned_by_student(): void
    {
        $owner = Student::findOrFail($this->makeStudent());
        $other = Student::findOrFail($this->makeStudent());
        $attId = $this->makeAttendance(['student_id' => $owner->id]);

        $attendance = Attendance::findOrFail($attId);

        $controller = app(StudentAttendanceController::class);
        $res = $controller->destroy($other, $attendance);

        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
    }
}
