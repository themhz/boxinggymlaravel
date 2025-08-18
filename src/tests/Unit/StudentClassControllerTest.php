<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\StudentClassController;
use App\Models\Student;
use App\Models\ClassModel;

class StudentClassControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard(); // allow create/update without fillable noise in tests
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    // ----------------- helpers -----------------

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

    private function attachStudentToClass(int $studentId, int $classId, array $pivot = []): void
    {
        DB::table('class_student')->insert([
            'class_id'   => $classId,
            'student_id' => $studentId,
            'status'     => $pivot['status'] ?? null,
            'note'       => $pivot['note'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ----------------- index() -----------------

    #[Test]
    public function it_index_lists_classes_for_student(): void
    {
        $student = Student::findOrFail($this->makeStudent());

        $c1 = $this->makeClass();
        $c2 = $this->makeClass();
        $this->attachStudentToClass($student->id, $c1, ['status' => 'active']);
        $this->attachStudentToClass($student->id, $c2, ['status' => 'waitlist']);

        $controller = app(StudentClassController::class);
        $res = $controller->index($student);

        $data = $res->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        // has lesson relation materialized
        $this->assertArrayHasKey('lesson', $data[0]);
        // pivot data is present via 'pivot'
        $this->assertArrayHasKey('pivot', $data[0]);
    }

    // ----------------- store() -----------------

    #[Test]
    public function it_store_adds_student_to_class_with_pivot_fields(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $classId = $this->makeClass();

        $controller = app(StudentClassController::class);
        $req = Request::create("/api/students/{$student->id}/classes", 'POST', [
            'class_id' => $classId,
            'status'   => 'active',
            'note'     => 'front row',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->store($req, $student);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('Student added to class', $data['message']);

        $this->assertDatabaseHas('class_student', [
            'class_id'   => $classId,
            'student_id' => $student->id,
            'status'     => 'active',
            'note'       => 'front row',
        ]);
    }

    #[Test]
    public function it_store_returns_409_when_already_enrolled(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $classId = $this->makeClass();
        $this->attachStudentToClass($student->id, $classId);

        $controller = app(StudentClassController::class);
        $req = Request::create("/api/students/{$student->id}/classes", 'POST', [
            'class_id' => $classId,
        ]);
        $req->headers->set('Accept', 'application/json');

        $res = $controller->store($req, $student);
        $this->assertSame(409, $res->status());
        $this->assertSame('Student already in this class', $res->getData(true)['message']);
    }

    // ----------------- update() -----------------

    #[Test]
    public function it_update_changes_pivot_fields_when_enrolled(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $classId = $this->makeClass();
        $this->attachStudentToClass($student->id, $classId, ['status' => 'pending', 'note' => 'x']);

        $class = ClassModel::findOrFail($classId);

        $controller = app(StudentClassController::class);
        $req = Request::create("/api/students/{$student->id}/classes/{$classId}", 'PATCH', [
            'status' => 'active',
            'note'   => 'updated note',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->update($req, $student, $class);
        $data = $res->getData(true);

        $this->assertSame('Enrollment updated', $data['message']);
        $this->assertSame('active', $data['pivot']['status']);
        $this->assertSame('updated note', $data['pivot']['note']);

        $this->assertDatabaseHas('class_student', [
            'class_id'   => $classId,
            'student_id' => $student->id,
            'status'     => 'active',
            'note'       => 'updated note',
        ]);
    }

    #[Test]
    public function it_update_returns_404_when_not_enrolled(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $classId = $this->makeClass();
        $class   = ClassModel::findOrFail($classId);

        $controller = app(StudentClassController::class);
        $req = Request::create("/api/students/{$student->id}/classes/{$classId}", 'PATCH', [
            'status' => 'active',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res = $controller->update($req, $student, $class);
        $this->assertSame(404, $res->status());
        $this->assertSame('Not enrolled in this class', $res->getData(true)['message']);
    }

    // ----------------- destroy() -----------------

    #[Test]
    public function it_destroy_detaches_enrollment_when_enrolled(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $classId = $this->makeClass();
        $this->attachStudentToClass($student->id, $classId, ['status' => 'active']);

        $class = ClassModel::findOrFail($classId);

        $controller = app(StudentClassController::class);
        $res  = $controller->destroy($student, $class);
        $data = $res->getData(true);

        $this->assertSame('Student removed from class', $data['message']);
        $this->assertDatabaseMissing('class_student', [
            'class_id'   => $classId,
            'student_id' => $student->id,
        ]);
    }

    #[Test]
    public function it_destroy_returns_404_when_not_enrolled(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $classId = $this->makeClass();
        $class   = ClassModel::findOrFail($classId);

        $controller = app(StudentClassController::class);
        $res = $controller->destroy($student, $class);

        $this->assertSame(404, $res->status());
        $this->assertSame('Not enrolled in this class', $res->getData(true)['message']);
    }
}
