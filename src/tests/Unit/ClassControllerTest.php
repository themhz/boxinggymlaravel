<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;


class ClassControllerTest extends TestCase
{
    use RefreshDatabase;

    // ---- Helpers -------------------------------------------------------------

    /** Create a lesson row and return its id */
    private function makeLesson(array $overrides = []): int
    {
        $row = array_merge([
            'title'       => 'Boxing',
            'description' => 'Basics',
            'level'       => 'all',
            'image'       => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ], $overrides);

        return DB::table('lessons')->insertGetId($row);
    }

    /** Create a teacher row and return its id */
    private function makeTeacher(array $overrides = []): int
    {
        $row = array_merge([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            // auto-unique email if not overridden
            'email'      => $overrides['email'] ?? ('teacher+'.Str::uuid().'@example.com'),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return DB::table('teachers')->insertGetId($row);
    }


    /** Create a student row and return its id */
    private function makeStudent(array $overrides = []): int
    {
        // minimal student shape; adapt if your schema needs more
        $row = array_merge([
            'name'       => 'Student One',
            'email'      => 'student1@example.com',
            'phone'      => null,
            'dob'        => null,
            'image'      => null,
            'user_id'    => DB::table('users')->insertGetId([
                'name'       => 'User S1',
                'email'      => 'user.s1@example.com',
                'password'   => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return DB::table('students')->insertGetId($row);
    }

    /** Create a class row and return its id (ensuring required NOT NULLs) */
    private function makeClass(array $overrides = []): int
    {
        $lessonId = $overrides['lesson_id'] ?? $this->makeLesson();

        $row = array_merge([
            'lesson_id'  => $lessonId,
            'start_time' => '09:00:00',
            'end_time'   => '10:00:00',
            'day'        => 'monday',
            'capacity'   => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return DB::table('classes')->insertGetId($row);
    }

    /** Attach a teacher to a class with pivot fields */
    private function attachTeacherToClass(int $classId, int $teacherId, array $pivot = []): void
    {
        DB::table('class_teacher')->insert(array_merge([
            'class_id'   => $classId,
            'teacher_id' => $teacherId,
            'role'       => 'assistant',
            'is_primary' => 0,
        ], $pivot));
    }

    /** Attach a student to a class with optional pivot fields */
    private function attachStudentToClass(int $classId, int $studentId, array $pivot = []): void
    {
        DB::table('class_student')->insert(array_merge([
            'class_id' => $classId,
            'student_id' => $studentId,
            'status' => null,
            'note'   => null,
        ], $pivot));
    }

    // ---- index() -------------------------------------------------------------

    #[Test]
    public function it_index_returns_classes_with_lesson_and_teachers_shape(): void
    {
        $lessonId = $this->makeLesson(['title' => 'Kickboxing']);
        $classId  = $this->makeClass(['lesson_id' => $lessonId, 'day' => 'tuesday', 'capacity' => 15]);

        $t1 = $this->makeTeacher(['first_name' => 'Mike', 'last_name' => 'Tyson', 'email' => 'mike@ex.com']);
        $t2 = $this->makeTeacher(['first_name' => 'Laila', 'last_name' => 'Ali',   'email' => 'laila@ex.com']);

        $this->attachTeacherToClass($classId, $t1, ['role' => 'head', 'is_primary' => 1]);
        $this->attachTeacherToClass($classId, $t2, ['role' => 'assistant', 'is_primary' => 0]);

        $controller = app(\App\Http\Controllers\ClassController::class);
        $response   = $controller->index();

        $data = $response->getData(true);
        $this->assertArrayHasKey('classes', $data);
        $this->assertCount(1, $data['classes']);

        $c = $data['classes'][0];
        $this->assertSame('tuesday', $c['day']);
        $this->assertSame(15, $c['capacity']);
        $this->assertSame('Kickboxing', $c['lesson']['title']);
        $this->assertCount(2, $c['teachers']);
        $this->assertSame('Mike Tyson', $c['teachers'][0]['name']);
        $this->assertSame(['role' => 'head', 'is_primary' => true], $c['teachers'][0]['pivot']);
    }

    // ---- schedule() ----------------------------------------------------------

    #[Test]
    public function it_schedule_groups_by_day_and_sorts_by_start_time(): void
    {
        $lessonA = $this->makeLesson(['title' => 'Boxing']);
        $lessonB = $this->makeLesson(['title' => 'Kickboxing']);

        // Same day, different times
        $c1 = $this->makeClass(['lesson_id' => $lessonA, 'day' => 'monday', 'start_time' => '08:00:00', 'end_time' => '09:00:00']);
        $c2 = $this->makeClass(['lesson_id' => $lessonB, 'day' => 'monday', 'start_time' => '07:30:00', 'end_time' => '08:30:00']);

        // Another day
        $c3 = $this->makeClass(['lesson_id' => $lessonA, 'day' => 'wednesday', 'start_time' => '18:00:00', 'end_time' => '19:00:00']);

        // Teachers (names included in schedule)
        $t1 = $this->makeTeacher(['first_name' => 'John', 'last_name' => 'Coach']);
        $t2 = $this->makeTeacher(['first_name' => 'Jane', 'last_name' => 'Mentor']);
        $this->attachTeacherToClass($c1, $t1, ['is_primary' => 1]);
        $this->attachTeacherToClass($c1, $t2, ['is_primary' => 0]);

        $controller = app(\App\Http\Controllers\ClassController::class);
        $response   = $controller->schedule();

        $data = $response->getData(true);
        $this->assertArrayHasKey('schedule', $data);
        $schedule = $data['schedule'];

        $this->assertArrayHasKey('monday', $schedule);
        $this->assertArrayHasKey('wednesday', $schedule);

        // monday is sorted by start_time: 07:30 then 08:00
        $this->assertSame('Kickboxing', $schedule['monday'][0]['class']);
        $this->assertSame('07:30:00',   $schedule['monday'][0]['start_time']);
        $this->assertSame('Boxing',     $schedule['monday'][1]['class']);
        $this->assertSame('08:00:00',   $schedule['monday'][1]['start_time']);

        // teachers array exists on items
        $this->assertIsArray($schedule['monday'][0]['teachers']);
        $this->assertIsArray($schedule['wednesday'][0]['teachers']);
    }

    // ---- show($id) -----------------------------------------------------------

    #[Test]
    public function it_show_returns_a_single_class_with_relations(): void
    {
        $lessonId = $this->makeLesson(['title' => 'Muay Thai']);
        $classId  = $this->makeClass(['lesson_id' => $lessonId]);

        $controller = app(\App\Http\Controllers\ClassController::class);
        $response   = $controller->show($classId);

        $data = $response->getData(true);
        $this->assertArrayHasKey('class', $data);
        $this->assertSame($classId, $data['class']['id']);
        $this->assertSame('Muay Thai', $data['class']['lesson']['title']);
    }

    #[Test]
    public function it_show_throws_404_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $controller = app(\App\Http\Controllers\ClassController::class);
        $controller->show(999999);
    }

    // ---- store(Request) ------------------------------------------------------

    #[Test]
    public function it_store_creates_class_with_valid_payload(): void
    {
        $lessonId = $this->makeLesson();

        $controller = app(\App\Http\Controllers\ClassController::class);
        $req = Request::create('/api/classes', 'POST', [
            'lesson_id'  => $lessonId,
            'start_time' => '09:00:00',
            'end_time'   => '10:00:00',
            'day'        => 'friday',
            'capacity'   => 25,
        ]);

        $res  = $controller->store($req);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('Class created successfully', $data['message']);
        $this->assertSame('friday', $data['class']['day']);
        $this->assertDatabaseHas('classes', [
            'lesson_id'  => $lessonId,
            'day'        => 'friday',
            'start_time' => '09:00:00',
            'end_time'   => '10:00:00',
            'capacity'   => 25,
        ]);
    }

    #[Test]
    public function it_store_fails_validation_when_payload_invalid(): void
    {
        $controller = app(\App\Http\Controllers\ClassController::class);

        // missing required fields -> validation exception
        $this->expectException(ValidationException::class);

        $req = Request::create('/api/classes', 'POST', [
            // 'lesson_id' missing
            'start_time' => '09:00:00',
            'end_time'   => '08:00:00', // also invalid because before start
            'day'        => '',
            'capacity'   => 0,
        ]);
        $controller->store($req);
    }

    // ---- update(Request, $id) ------------------------------------------------

    #[Test]
    public function it_update_edits_a_class_with_valid_payload(): void
    {
        $classId = $this->makeClass(['day' => 'monday', 'capacity' => 10]);

        $controller = app(\App\Http\Controllers\ClassController::class);
        $req = Request::create("/api/classes/{$classId}", 'PUT', [
            'day'      => 'thursday',
            'capacity' => 30,
        ]);

        $res  = $controller->update($req, $classId);
        $data = $res->getData(true);

        $this->assertSame('Class updated successfully', $data['message']);
        $this->assertSame('thursday', $data['class']['day']);
        $this->assertSame(30, $data['class']['capacity']);
        $this->assertDatabaseHas('classes', [
            'id'       => $classId,
            'day'      => 'thursday',
            'capacity' => 30,
        ]);
    }

    #[Test]
    public function it_update_fails_validation_when_invalid_payload(): void
    {
        $classId = $this->makeClass(['start_time' => '09:00:00', 'end_time' => '10:00:00']);

        $controller = app(\App\Http\Controllers\ClassController::class);
        $req = Request::create("/api/classes/{$classId}", 'PUT', [
            'start_time' => '09:00:00',
            'end_time'   => '07:00:00', // invalid: not after start_time
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->update($req, $classId);
    }


    #[Test]
    public function it_update_throws_404_when_not_found(): void
    {
        $controller = app(\App\Http\Controllers\ClassController::class);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $req = Request::create('/api/classes/999999', 'PUT', ['day' => 'sunday']);
        $controller->update($req, 999999);
    }

    // ---- destroy($id) --------------------------------------------------------

    #[Test]
    public function it_destroy_deletes_existing_class_and_returns_deleted_1(): void
    {
        $classId = $this->makeClass();

        $controller = app(\App\Http\Controllers\ClassController::class);
        $res = $controller->destroy($classId);

        $data = $res->getData(true);
        $this->assertSame(200, $res->status());
        $this->assertSame(1, $data['deleted']);
        $this->assertDatabaseMissing('classes', ['id' => $classId]);
    }

    #[Test]
    public function it_destroy_returns_404_when_class_missing(): void
    {
        $controller = app(\App\Http\Controllers\ClassController::class);
        $res = $controller->destroy(999999);

        $this->assertSame(404, $res->status());
        $this->assertSame(['deleted' => 0], $res->getData(true));
    }

    // ---- students($id) -------------------------------------------------------

    #[Test]
    public function it_students_returns_students_of_a_class(): void
    {
        $classId   = $this->makeClass();
        $studentId = $this->makeStudent();

        $this->attachStudentToClass($classId, $studentId);

        $controller = app(\App\Http\Controllers\ClassController::class);
        $res = $controller->students($classId);

        $data = $res->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertSame($studentId, $data[0]['id']);
    }

    // ---- addStudent(Request, $id) --------------------------------------------

    #[Test]
    public function it_add_student_enrolls_student_to_class(): void
    {
        $classId   = $this->makeClass();
        $studentId = $this->makeStudent();

        $controller = app(\App\Http\Controllers\ClassController::class);
        $req = Request::create("/api/classes/{$classId}/students", 'POST', [
            'student_id' => $studentId,
        ]);

        $res  = $controller->addStudent($req, $classId);
        $data = $res->getData(true);

        $this->assertSame('Student added to class', $data['message']);
        $this->assertDatabaseHas('class_student', [
            'class_id' => $classId,
            'student_id' => $studentId,
        ]);
    }

    #[Test]
    public function it_add_student_fails_when_student_missing(): void
    {
        $classId = $this->makeClass();

        $controller = app(\App\Http\Controllers\ClassController::class);
        $req = Request::create("/api/classes/{$classId}/students", 'POST', [
            'student_id' => 999999, // not exists
        ]);

        $this->expectException(ValidationException::class);
        $controller->addStudent($req, $classId);
    }

    // ---- updateStudent(Request, $classId, $studentId) ------------------------

    #[Test]
    public function it_update_student_updates_pivot_fields(): void
    {
        $classId   = $this->makeClass();
        $studentId = $this->makeStudent();
        $this->attachStudentToClass($classId, $studentId, ['status' => 'active', 'note' => null]);

        $controller = app(\App\Http\Controllers\ClassController::class);
        $req = Request::create("/api/classes/{$classId}/students/{$studentId}", 'PATCH', [
            'status' => 'inactive',
            'note'   => 'On vacation',
        ]);

        $res  = $controller->updateStudent($req, $classId, $studentId);
        $data = $res->getData(true);

        $this->assertSame(1, $data['result']);
        $this->assertSame('Student updated in class', $data['message']);

        $this->assertDatabaseHas('class_student', [
            'class_id' => $classId,
            'student_id' => $studentId,
            'status' => 'inactive',
            'note'   => 'On vacation',
        ]);
    }

    #[Test]
    public function it_update_student_returns_404_when_class_missing(): void
    {
        $controller = app(\App\Http\Controllers\ClassController::class);
        $req = Request::create('/api/classes/999999/students/1', 'PATCH', [
            'status' => 'inactive',
        ]);

        $res = $controller->updateStudent($req, 999999, 1);
        $this->assertSame(404, $res->status());
        $this->assertSame(0, $res->getData(true)['result']);
    }

    // ---- patchStudent(Request, $classId, $studentId) -------------------------

    #[Test]
    public function it_patch_student_returns_400_when_no_fields(): void
    {
        $classId   = $this->makeClass();
        $studentId = $this->makeStudent();
        $this->attachStudentToClass($classId, $studentId);

        $controller = app(\App\Http\Controllers\ClassController::class);
        $req = Request::create("/api/classes/{$classId}/students/{$studentId}", 'PATCH', []);

        $res = $controller->patchStudent($req, $classId, $studentId);
        $this->assertSame(400, $res->status());
        $this->assertSame('No fields provided', $res->getData(true)['message']);
    }

    #[Test]
    public function it_patch_student_updates_partial_fields(): void
    {
        $classId   = $this->makeClass();
        $studentId = $this->makeStudent();
        $this->attachStudentToClass($classId, $studentId, ['status' => 'active', 'note' => null]);

        $controller = app(\App\Http\Controllers\ClassController::class);
        $req = Request::create("/api/classes/{$classId}/students/{$studentId}", 'PATCH', [
            'note' => 'Late arrival',
        ]);

        $res = $controller->patchStudent($req, $classId, $studentId);
        $this->assertSame(1, $res->getData(true)['result']);

        $this->assertDatabaseHas('class_student', [
            'class_id' => $classId,
            'student_id' => $studentId,
            'note' => 'Late arrival',
        ]);
    }

    // ---- removeStudent($classId, $studentId) --------------------------------

    #[Test]
    public function it_remove_student_detaches_student(): void
    {
        $classId   = $this->makeClass();
        $studentId = $this->makeStudent();
        $this->attachStudentToClass($classId, $studentId);

        $controller = app(\App\Http\Controllers\ClassController::class);
        $res = $controller->removeStudent($classId, $studentId);

        $data = $res->getData(true);
        $this->assertSame(1, $data['result']);
        $this->assertSame('Student removed from class', $data['message']);
        $this->assertDatabaseMissing('class_student', [
            'class_id' => $classId,
            'student_id' => $studentId,
        ]);
    }

    #[Test]
    public function it_remove_student_returns_404_when_class_missing(): void
    {
        $controller = app(\App\Http\Controllers\ClassController::class);
        $res = $controller->removeStudent(999999, 1);

        $this->assertSame(404, $res->status());
        $this->assertSame(0, $res->getData(true)['result']);
    }
}
