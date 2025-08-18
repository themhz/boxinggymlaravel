<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\TeacherController;
use App\Models\Teacher;

class TeacherControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Avoid MassAssignmentException for ::create / ->fill in unit tests
        Model::unguard();
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    // ----------------- Helpers -----------------

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

    private function makeTeacher(array $overrides = []): int
    {
        $userId = $overrides['user_id'] ?? $this->makeUser();
        $defaults = [
            'user_id'    => $userId,
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'teacher_'.uniqid('', true).'@example.com',
            'phone'      => null,
            'bio'        => null,
            'hire_date'  => null,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('teachers')->insertGetId(array_replace($defaults, $overrides));
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
        $lessonId = $overrides['lesson_id'] ?? $this->makeLesson();

        $defaults = [
            'lesson_id'  => $lessonId,
            'start_time' => '09:00:00',
            'end_time'   => '10:00:00',
            'day'        => 'monday',
            'capacity'   => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // ensure we don't accidentally pass teacher_id to the classes table
        unset($overrides['teacher_id']);

        return DB::table('classes')->insertGetId(array_replace($defaults, $overrides));
    }


    // ----------------- index() -----------------

    #[Test]
    public function it_index_returns_teachers_ordered_desc_by_created_at(): void
    {
        // older
        $olderId = $this->makeTeacher(['created_at' => now()->subDay()]);
        // newer
        $newerId = $this->makeTeacher(['created_at' => now()]);

        $controller = app(TeacherController::class);
        $res = $controller->index();

        $data = $res->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        // first item should be the newer teacher (desc by created_at)
        $this->assertSame($newerId, $data[0]['id']);
        $this->assertSame($olderId, $data[1]['id']);
    }

    // ----------------- show() -----------------

    #[Test]
    public function it_show_returns_a_single_teacher(): void
    {
        $id = $this->makeTeacher(['first_name' => 'Laila', 'last_name' => 'Ali']);
        $teacher = Teacher::findOrFail($id);

        $controller = app(TeacherController::class);
        $res = $controller->show($teacher);

        $data = $res->getData(true);
        $this->assertSame($id, $data['id']);
        $this->assertSame('Laila', $data['first_name']);
        $this->assertSame('Ali',   $data['last_name']);
    }

    // ----------------- store() -----------------

    #[Test]
    public function it_store_creates_teacher_and_defaults_is_active_true(): void
    {
        $userId = $this->makeUser();        
        $controller = app(TeacherController::class);
        $req = Request::create('/api/teachers', 'POST', [
            'user_id'    => $userId,
            'first_name' => 'Mike',
            'last_name'  => 'Tyson',
            'email'      => 'mike.tyson+'.uniqid().'@example.com',
            // omit is_active -> should default to true
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->store($req);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('Teacher created.', $data['message']);
        $this->assertTrue((bool) $data['data']['is_active']);

        $this->assertDatabaseHas('teachers', [
            'user_id'    => $userId,
            'first_name' => 'Mike',
            'last_name'  => 'Tyson',
            'is_active'  => 1,
        ]);
    }

    #[Test]
    public function it_store_fails_when_user_missing_or_email_not_unique(): void
    {
        $controller = app(TeacherController::class);

        // user must exist
        $req1 = Request::create('/api/teachers', 'POST', [
            'user_id'    => 999999, // not exists
            'first_name' => 'Bad',
            'last_name'  => 'User',
            'email'      => 'bad+'.uniqid().'@example.com',
        ]);
        $req1->headers->set('Accept', 'application/json');
        $this->expectException(ValidationException::class);
        $controller->store($req1);

        // unique email
        $userId = $this->makeUser();
        $email  = 'dup+'.uniqid().'@example.com';
        $this->makeTeacher(['user_id' => $userId, 'email' => $email]);

        $req2 = Request::create('/api/teachers', 'POST', [
            'user_id'    => $this->makeUser(),
            'first_name' => 'Dup',
            'last_name'  => 'Email',
            'email'      => $email, // duplicate
        ]);
        $req2->headers->set('Accept', 'application/json');
        $this->expectException(ValidationException::class);
        $controller->store($req2);
    }

    // ----------------- update() -----------------

    #[Test]
    public function it_update_updates_fields_and_returns_fresh(): void
    {
        $id = $this->makeTeacher([
            'first_name' => 'Old',
            'last_name'  => 'Name',
            'email'      => 'old+'.uniqid().'@example.com',
            'is_active'  => 0,
        ]);
        $teacher = Teacher::findOrFail($id);

        $controller = app(TeacherController::class);
        $req = Request::create("/api/teachers/{$id}", 'PUT', [
            'first_name' => 'New',
            'last_name'  => 'Name',
            'is_active'  => true,
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->update($req, $teacher);
        $data = $res->getData(true);

        $this->assertSame('Teacher updated.', $data['message']);
        $this->assertSame('New', $data['data']['first_name']);
        $this->assertSame('Name', $data['data']['last_name']);
        $this->assertTrue((bool) $data['data']['is_active']);

        $this->assertDatabaseHas('teachers', [
            'id'         => $id,
            'first_name' => 'New',
            'last_name'  => 'Name',
            'is_active'  => 1,
        ]);
    }

    #[Test]
    public function it_update_fails_when_email_not_unique(): void
    {
        $firstId  = $this->makeTeacher(['email' => 'one+'.uniqid().'@example.com']);
        $secondId = $this->makeTeacher(['email' => 'two+'.uniqid().'@example.com']);

        $controller = app(TeacherController::class);
        $teacher    = Teacher::findOrFail($firstId);

        $req = Request::create("/api/teachers/{$firstId}", 'PUT', [
            'email' => DB::table('teachers')->where('id', $secondId)->value('email'),
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->update($req, $teacher);
    }

    // ----------------- destroy() -----------------

    #[Test]
    public function it_destroy_deletes_teacher_and_returns_message(): void
    {
        $id = $this->makeTeacher();
        $teacher = Teacher::findOrFail($id);

        $controller = app(TeacherController::class);
        $req = Request::create("/api/teachers/{$id}", 'DELETE');
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->destroy($req, $teacher);
        $data = $res->getData(true);

        $this->assertSame('Teacher deleted.', $data['message']);
        $this->assertDatabaseMissing('teachers', ['id' => $id]);
    }

    // ----------------- lessons() -----------------

    private function attachTeacherToClass(int $classId, int $teacherId, array $pivot = []): void
    {
        DB::table('class_teacher')->insert(array_merge([
            'class_id'   => $classId,
            'teacher_id' => $teacherId,
            'role'       => 'assistant',
            'is_primary' => 0,
        ], $pivot));
    }

    #[Test]
    public function it_lessons_returns_lessons_taught_by_teacher_via_classes(): void
    {
        $teacherId = $this->makeTeacher(['first_name' => 'Alex', 'last_name' => 'Coach']);
        $teacher   = Teacher::findOrFail($teacherId);

        $lessonA = $this->makeLesson(['title' => 'Boxing']);
        $lessonB = $this->makeLesson(['title' => 'Kickboxing']);
        $lessonC = $this->makeLesson(['title' => 'Muay Thai']); // not taught by this teacher

        // Create classes WITHOUT teacher_id, then attach via pivot
        $class1 = $this->makeClass([
            'lesson_id'  => $lessonA,
            'start_time' => '08:00:00',
            'end_time'   => '09:00:00',
            'day'        => 'monday',
        ]);
        $this->attachTeacherToClass($class1, $teacherId, ['is_primary' => 1]);

        $class2 = $this->makeClass([
            'lesson_id'  => $lessonB,
            'start_time' => '18:00:00',
            'end_time'   => '19:00:00',
            'day'        => 'wednesday',
        ]);
        $this->attachTeacherToClass($class2, $teacherId);

        // A class for someone else (should not appear)
        $otherClass = $this->makeClass(['lesson_id' => $lessonC]);
        $this->attachTeacherToClass($otherClass, $this->makeTeacher());


        // A class for someone else (should not appear)
        $otherClass = $this->makeClass(['lesson_id' => $lessonC]);
        $this->attachTeacherToClass($otherClass, $this->makeTeacher());

        $controller = app(\App\Http\Controllers\TeacherController::class);
        $res = $controller->lessons($teacher);

        $data = $res->getData(true);
        $this->assertIsArray($data);
        $titles = collect($data)->pluck('title');

        $this->assertTrue($titles->contains('Boxing'));
        $this->assertTrue($titles->contains('Kickboxing'));
        $this->assertFalse($titles->contains('Muay Thai'));

        // Returned lessons include their classes filtered by this teacher
        foreach ($data as $lesson) {
            $this->assertArrayHasKey('classes', $lesson);
            foreach ($lesson['classes'] as $cls) {
                // since controller no longer selects teacher_id, just ensure the class is one of the attached ones
                $this->assertContains($cls['id'], [$class1, $class2]);
            }
        }
    }

}
