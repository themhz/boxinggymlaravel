<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Http\Controllers\TeacherClassController;
use App\Models\Teacher;
use App\Models\ClassModel;

class TeacherClassControllerTest extends TestCase
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

    // ---------- Helpers ----------

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

    private function makeTeacher(array $overrides = []): int
    {
        $defaults = [
            'user_id'    => null, // optional depending on your schema
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'teacher_'.uniqid('', true).'@example.com', // avoid unique collisions
            'phone'      => null,
            'bio'        => null,
            'hire_date'  => null,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('teachers')->insertGetId(array_replace($defaults, $overrides));
    }

    /** Attach a teacher to a class via pivot (optionally set timestamps). */
    private function attachTeacherToClass(int $teacherId, int $classId, array $pivot = []): void
    {
        $defaults = [
            'teacher_id' => $teacherId,
            'class_id'   => $classId,
            'role'       => $pivot['role']       ?? null,
            'is_primary' => $pivot['is_primary'] ?? 0,
            'created_at' => $pivot['created_at'] ?? now(),
            'updated_at' => $pivot['updated_at'] ?? now(),
        ];
        DB::table('class_teacher')->insert($defaults);
    }

    // ---------- index() ----------

    #[Test]
    public function it_index_lists_classes_for_teacher_ordered_by_pivot_created_at(): void
    {
        $teacherId = $this->makeTeacher();
        $classOld  = $this->makeClass(['day' => 'tuesday']);
        $classNew  = $this->makeClass(['day' => 'wednesday']);

        // Attach with distinct pivot created_at to test ordering
        $this->attachTeacherToClass($teacherId, $classOld, ['created_at' => now()->subDay()]);
        $this->attachTeacherToClass($teacherId, $classNew, ['created_at' => now()]); // newer

        $controller = app(TeacherClassController::class);
        $teacher    = Teacher::findOrFail($teacherId);

        $res  = $controller->index($teacher);
        $data = $res->getData(true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        // Should be ordered DESC by pivot created_at -> classNew first
        $this->assertSame($classNew, $data[0]['id']);
        $this->assertSame($classOld, $data[1]['id']);

        // relations loaded
        $this->assertArrayHasKey('lesson', $data[0]);
        $this->assertArrayHasKey('teachers', $data[0]);
    }

    // ---------- show() ----------

    #[Test]
    public function it_show_returns_class_when_teacher_attached(): void
    {
        $teacherId = $this->makeTeacher(['first_name' => 'Alex']);
        $classId   = $this->makeClass();
        $this->attachTeacherToClass($teacherId, $classId, ['role' => 'Lead', 'is_primary' => 1]);

        $controller = app(TeacherClassController::class);
        $teacher    = Teacher::findOrFail($teacherId);
        $class      = ClassModel::findOrFail($classId);

        $res  = $controller->show($teacher, $class);
        $data = $res->getData(true);

        $this->assertSame($classId, $data['id']);
        $this->assertArrayHasKey('lesson', $data);
        $this->assertArrayHasKey('teachers', $data);
    }

    #[Test]
    public function it_show_returns_404_when_teacher_not_attached_to_class(): void
    {
        $teacherId = $this->makeTeacher();
        $classId   = $this->makeClass();

        $controller = app(TeacherClassController::class);
        $teacher    = Teacher::findOrFail($teacherId);
        $class      = ClassModel::findOrFail($classId);

        $res = $controller->show($teacher, $class);
        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
    }

    // ---------- store() ----------

    #[Test]
    public function it_store_attaches_teacher_to_class_with_pivot_fields(): void
    {
        $teacherId = $this->makeTeacher();
        $classId   = $this->makeClass();

        $controller = app(TeacherClassController::class);
        $teacher    = Teacher::findOrFail($teacherId);

        $req = Request::create("/api/teachers/{$teacherId}/classes", 'POST', [
            'class_id'   => $classId,
            'role'       => 'Assistant',
            'is_primary' => true,
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->store($req, $teacher);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('Teacher attached to class.', $data['message']);

        $this->assertDatabaseHas('class_teacher', [
            'teacher_id' => $teacherId,
            'class_id'   => $classId,
            'role'       => 'Assistant',
            'is_primary' => 1,
        ]);
    }

    #[Test]
    public function it_store_returns_409_when_already_attached(): void
    {
        $teacherId = $this->makeTeacher();
        $classId   = $this->makeClass();

        // Already attached
        $this->attachTeacherToClass($teacherId, $classId);

        $controller = app(TeacherClassController::class);
        $teacher    = Teacher::findOrFail($teacherId);

        $req = Request::create("/api/teachers/{$teacherId}/classes", 'POST', [
            'class_id' => $classId,
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->store($req, $teacher);
        $data = $res->getData(true);

        $this->assertSame(409, $res->status());
        $this->assertSame('This teacher is already attached to that class.', $data['message']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('class_id', $data['errors']);
    }

    // ---------- update() ----------

    #[Test]
    public function it_update_updates_pivot_fields(): void
    {
        $teacherId = $this->makeTeacher(['first_name' => 'Maria']);
        $classId   = $this->makeClass();

        // attach first
        $this->attachTeacherToClass($teacherId, $classId, ['role' => null, 'is_primary' => 0]);

        $controller = app(TeacherClassController::class);
        $teacher    = Teacher::findOrFail($teacherId);
        $class      = ClassModel::findOrFail($classId);

        $req = Request::create("/api/teachers/{$teacherId}/classes/{$classId}", 'PATCH', [
            'role'       => 'Lead',
            'is_primary' => true,
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->update($req, $teacher, $class);
        $data = $res->getData(true);

        $this->assertSame('Pivot updated.', $data['message']);

        // Check pivot in DB
        $this->assertDatabaseHas('class_teacher', [
            'teacher_id' => $teacherId,
            'class_id'   => $classId,
            'role'       => 'Lead',
            'is_primary' => 1,
        ]);

        // Response includes class with teachers; ensure pivot present
        $this->assertArrayHasKey('teachers', $data['data']);
        $teacherRow = collect($data['data']['teachers'])->firstWhere('id', $teacherId);
        $this->assertNotNull($teacherRow);
        $this->assertArrayHasKey('pivot', $teacherRow);
        $this->assertSame('Lead', $teacherRow['pivot']['role']);
        $this->assertTrue((bool) $teacherRow['pivot']['is_primary']);
    }

    #[Test]
    public function it_update_returns_404_when_teacher_not_attached(): void
    {
        $teacherId = $this->makeTeacher();
        $classId   = $this->makeClass();

        $controller = app(TeacherClassController::class);
        $teacher    = Teacher::findOrFail($teacherId);
        $class      = ClassModel::findOrFail($classId);

        $req = Request::create("/api/teachers/{$teacherId}/classes/{$classId}", 'PATCH', [
            'role' => 'Lead',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res = $controller->update($req, $teacher, $class);
        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
    }

    // ---------- destroy() ----------

    #[Test]
    public function it_destroy_detaches_teacher_from_class(): void
    {
        $teacherId = $this->makeTeacher();
        $classId   = $this->makeClass();

        $this->attachTeacherToClass($teacherId, $classId);

        $controller = app(TeacherClassController::class);
        $teacher    = Teacher::findOrFail($teacherId);
        $class      = ClassModel::findOrFail($classId);

        $res = $controller->destroy($teacher, $class);
        $this->assertSame(200, $res->status());
        $this->assertSame('Teacher detached from class.', $res->getData(true)['message']);

        $this->assertDatabaseMissing('class_teacher', [
            'teacher_id' => $teacherId,
            'class_id'   => $classId,
        ]);
    }
}
