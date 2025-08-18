<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\StudentExerciseController;
use App\Models\Student;
use App\Models\StudentExercise;
use App\Models\User;

class StudentExerciseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // allow mass assignment for create/update in unit tests
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
            'name'       => 'User ' . uniqid(),
            'email'      => 'user_' . uniqid('', true) . '@example.com',
            'password'   => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('users')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeStudent(array $overrides = []): int
    {
        $defaults = [
            'name'       => 'Student ' . uniqid(),
            'email'      => 'student_' . uniqid('', true) . '@example.com',
            'phone'      => null,
            'dob'        => null,
            'image'      => null,
            'user_id'    => $this->makeUser(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('students')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeExercise(array $overrides = []): int
    {
        $defaults = [
            'name'          => 'Push Up',
            'description'   => 'Upper body',
            'exercise_type' => 'strength',
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
        return DB::table('exercises')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeStudentExercise(array $overrides = []): int
    {
        $defaults = [
            'student_id'       => $this->makeStudent(),
            'exercise_id'      => $this->makeExercise(),
            'sets'             => null,
            'repetitions'      => null,
            'weight'           => null,
            'duration_seconds' => null,
            'note'             => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ];
        return DB::table('student_exercises')->insertGetId(array_replace($defaults, $overrides));
    }

    // ---------------- index() ----------------

    #[Test]
    public function it_index_lists_exercises_for_student_ordered_desc(): void
    {
        $studentA = Student::findOrFail($this->makeStudent());
        $studentB = Student::findOrFail($this->makeStudent());

        $ex1 = $this->makeExercise(['name' => 'Jumping Jacks']);
        $ex2 = $this->makeExercise(['name' => 'Plank']);
        $ex3 = $this->makeExercise(['name' => 'Burpee']);

        // two items for A (newest should come first), one for B
        $id1 = $this->makeStudentExercise([
            'student_id'  => $studentA->id,
            'exercise_id' => $ex1,
            'created_at'  => now()->subMinute(),
        ]);
        $id2 = $this->makeStudentExercise([
            'student_id'  => $studentA->id,
            'exercise_id' => $ex2,
            'created_at'  => now(),
        ]);
        $id3 = $this->makeStudentExercise([
            'student_id'  => $studentB->id,
            'exercise_id' => $ex3,
        ]);

        $controller = app(StudentExerciseController::class);
        $res = $controller->index($studentA);

        $data = $res->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        // orderByDesc(created_at)
        $this->assertSame($id2, $data[0]['id']);
        $this->assertSame($id1, $data[1]['id']);

        // relation present
        $this->assertArrayHasKey('exercise', $data[0]);
    }

    // ---------------- show() ----------------

    #[Test]
    public function it_show_returns_single_student_exercise_when_owned(): void
    {
        $studentId = $this->makeStudent();
        $exerciseId = $this->makeExercise(['name' => 'Row']);
        $seId = $this->makeStudentExercise(['student_id' => $studentId, 'exercise_id' => $exerciseId]);

        $student = Student::findOrFail($studentId);
        $se      = StudentExercise::findOrFail($seId);

        $controller = app(StudentExerciseController::class);
        $res = $controller->show($student, $se);

        $data = $res->getData(true);
        $this->assertSame($seId, $data['id']);
        $this->assertArrayHasKey('exercise', $data);
        $this->assertSame('Row', $data['exercise']['name']);
    }

    #[Test]
    public function it_show_returns_404_when_not_owned_by_student(): void
    {
        $owner   = Student::findOrFail($this->makeStudent());
        $other   = Student::findOrFail($this->makeStudent());
        $seId    = $this->makeStudentExercise(['student_id' => $owner->id]);

        $se = StudentExercise::findOrFail($seId);

        $controller = app(StudentExerciseController::class);
        $res = $controller->show($other, $se);

        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
    }

    // ---------------- store() ---------------

    #[Test]
    public function it_store_creates_student_exercise(): void
    {
        $student  = Student::findOrFail($this->makeStudent());
        $exercise = $this->makeExercise(['name' => 'Squat']);

        $controller = app(StudentExerciseController::class);
        $req = Request::create("/api/students/{$student->id}/exercises", 'POST', [
            'exercise_id'      => $exercise,
            'sets'             => 3,
            'repetitions'      => 10,
            'weight'           => 50.5,
            'duration_seconds' => null,
            'note'             => 'Go slow',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->store($req, $student);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('Exercise assigned successfully.', $data['message']);
        $this->assertSame(3, $data['data']['sets']);
        $this->assertArrayHasKey('exercise', $data['data']);
        $this->assertSame('Squat', $data['data']['exercise']['name']);

        $this->assertDatabaseHas('student_exercises', [
            'student_id'  => $student->id,
            'exercise_id' => $exercise,
            'sets'        => 3,
            'repetitions' => 10,
        ]);
    }

    #[Test]
    public function it_store_enforces_unique_exercise_per_student(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $exercise = $this->makeExercise();

        // pre-existing record (same student + exercise)
        $this->makeStudentExercise(['student_id' => $student->id, 'exercise_id' => $exercise]);

        $controller = app(StudentExerciseController::class);
        $req = Request::create("/api/students/{$student->id}/exercises", 'POST', [
            'exercise_id' => $exercise, // duplicate for same student
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->store($req, $student);
    }

    // ---------------- update() ---------------

    #[Test]
    public function it_update_edits_fields_when_owned(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $seId    = $this->makeStudentExercise([
            'student_id'  => $student->id,
            'exercise_id' => $this->makeExercise(['name' => 'Bench']),
            'sets'        => 3,
            'repetitions' => 8,
            'note'        => 'orig',
        ]);

        $se = StudentExercise::findOrFail($seId);

        $controller = app(StudentExerciseController::class);
        $req = Request::create("/api/students/{$student->id}/exercises/{$seId}", 'PATCH', [
            'sets'        => 4,
            'repetitions' => 6,
            'note'        => 'updated',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->update($req, $student, $se);
        $data = $res->getData(true);

        $this->assertSame('Exercise updated successfully.', $data['message']);
        $this->assertSame(4, $data['data']['sets']);
        $this->assertSame(6, $data['data']['repetitions']);
        $this->assertSame('updated', $data['data']['note']);

        $this->assertDatabaseHas('student_exercises', [
            'id'          => $seId,
            'sets'        => 4,
            'repetitions' => 6,
            'note'        => 'updated',
        ]);
    }

    #[Test]
    public function it_update_returns_404_when_not_owned_by_student(): void
    {
        $owner = Student::findOrFail($this->makeStudent());
        $other = Student::findOrFail($this->makeStudent());
        $seId  = $this->makeStudentExercise(['student_id' => $owner->id]);

        $se = StudentExercise::findOrFail($seId);

        $controller = app(StudentExerciseController::class);
        $req = Request::create("/api/students/{$other->id}/exercises/{$seId}", 'PATCH', ['note' => 'hack']);
        $req->headers->set('Accept', 'application/json');

        $res = $controller->update($req, $other, $se);
        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
    }

    #[Test]
    public function it_update_enforces_unique_exercise_per_student_when_changing_exercise(): void
    {
        $student = Student::findOrFail($this->makeStudent());

        $exerciseA = $this->makeExercise(['name' => 'Deadlift']);
        $exerciseB = $this->makeExercise(['name' => 'Clean']);

        // two records owned by same student, each for a different exercise
        $idA = $this->makeStudentExercise(['student_id' => $student->id, 'exercise_id' => $exerciseA]);
        $idB = $this->makeStudentExercise(['student_id' => $student->id, 'exercise_id' => $exerciseB]);

        $recordB = StudentExercise::findOrFail($idB);

        $controller = app(StudentExerciseController::class);
        // attempt to change B's exercise to A (dup)
        $req = Request::create("/api/students/{$student->id}/exercises/{$idB}", 'PATCH', [
            'exercise_id' => $exerciseA,
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->update($req, $student, $recordB);
    }

    // ---------------- destroy() --------------

    #[Test]
    public function it_destroy_deletes_record_when_owned(): void
    {
        $student = Student::findOrFail($this->makeStudent());
        $seId    = $this->makeStudentExercise(['student_id' => $student->id]);

        $se = StudentExercise::findOrFail($seId);

        $controller = app(StudentExerciseController::class);
        $res  = $controller->destroy(Request::create('', 'DELETE'), $student, $se);
        $data = $res->getData(true);

        $this->assertSame('Exercise deleted successfully.', $data['message']);
        $this->assertDatabaseMissing('student_exercises', ['id' => $seId]);
    }

    #[Test]
    public function it_destroy_returns_404_when_not_owned_by_student(): void
    {
        $owner = Student::findOrFail($this->makeStudent());
        $other = Student::findOrFail($this->makeStudent());
        $seId  = $this->makeStudentExercise(['student_id' => $owner->id]);

        $se = StudentExercise::findOrFail($seId);

        $controller = app(StudentExerciseController::class);
        $res = $controller->destroy(Request::create('', 'DELETE'), $other, $se);

        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
    }
}
