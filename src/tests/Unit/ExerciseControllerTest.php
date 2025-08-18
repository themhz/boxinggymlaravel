<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\ExerciseController;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class ExerciseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // avoid MassAssignmentException for Exercise::create(...)
        Model::unguard();
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    // ---------------- Helpers ----------------

    private function makeExercise(array $overrides = []): int
    {
        $defaults = [
            'name'          => 'Push Up',
            'description'   => 'Upper body exercise',
            'exercise_type' => 'strength',
            'created_at'    => now(),
            'updated_at'    => now(),
        ];

        return DB::table('exercises')->insertGetId(array_replace($defaults, $overrides));
    }

    // ---------------- index() ----------------

    #[Test]
    public function it_index_returns_exercises_with_selected_fields(): void
    {
        $this->makeExercise(['name' => 'Jumping Jacks', 'exercise_type' => 'cardio']);
        $this->makeExercise(['name' => 'Plank', 'exercise_type' => 'core']);

        $controller = app(ExerciseController::class);
        $response   = $controller->index();   // returns a Collection

        $data = $response->toArray();
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        // shape check
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('description', $data[0]);
        $this->assertArrayHasKey('exercise_type', $data[0]);
    }

    // ---------------- show($id) -------------

    #[Test]
    public function it_show_returns_single_exercise(): void
    {
        $id = $this->makeExercise(['name' => 'Burpee', 'exercise_type' => 'cardio']);

        $controller = app(ExerciseController::class);
        $res = $controller->show($id);

        $data = $res->getData(true);
        $this->assertSame($id, $data['id']);
        $this->assertSame('Burpee', $data['name']);
        $this->assertSame('cardio', $data['exercise_type']);
    }

    #[Test]
    public function it_show_throws_404_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $controller = app(ExerciseController::class);
        $controller->show(999999);
    }

    // ---------------- store(Request) --------

    #[Test]
    public function it_store_creates_exercise_with_valid_payload(): void
    {
        $controller = app(ExerciseController::class);
        $req = Request::create('/api/exercises', 'POST', [
            'name'          => 'Lunge',
            'description'   => 'Leg strength',
            'exercise_type' => 'strength',
        ]);

        $res  = $controller->store($req);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('Lunge', $data['name']);
        $this->assertSame('strength', $data['exercise_type']);

        $this->assertDatabaseHas('exercises', ['name' => 'Lunge']);
    }

    #[Test]
    public function it_store_fails_when_name_missing(): void
    {
        $controller = app(ExerciseController::class);
        $req = Request::create('/api/exercises', 'POST', [
            // 'name' missing
            'description'   => 'desc',
            'exercise_type' => 'cardio',
        ]);

        $this->expectException(ValidationException::class);
        $controller->store($req);
    }

    #[Test]
    public function it_store_fails_when_name_not_unique(): void
    {
        $this->makeExercise(['name' => 'Squat']);

        $controller = app(ExerciseController::class);
        $req = Request::create('/api/exercises', 'POST', [
            'name'          => 'Squat', // duplicate
            'description'   => 'Lower body',
            'exercise_type' => 'strength',
        ]);

        $this->expectException(ValidationException::class);
        $controller->store($req);
    }

    // ---------------- update(Request, $id) ----

    #[Test]
    public function it_update_updates_fields(): void
    {
        $id = $this->makeExercise(['name' => 'Sit Up', 'exercise_type' => 'core']);

        $controller = app(ExerciseController::class);
        $req = Request::create("/api/exercises/{$id}", 'PUT', [
            'name'          => 'Sit-Up',
            'description'   => 'Abdominal exercise',
            'exercise_type' => 'core',
        ]);

        $res  = $controller->update($req, $id);
        $data = $res->getData(true);

        $this->assertSame($id, $data['id']);
        $this->assertSame('Sit-Up', $data['name']);
        $this->assertSame('Abdominal exercise', $data['description']);

        $this->assertDatabaseHas('exercises', [
            'id'   => $id,
            'name' => 'Sit-Up',
        ]);
    }

    #[Test]
    public function it_update_fails_when_name_not_unique(): void
    {
        $firstId  = $this->makeExercise(['name' => 'Row']);
        $secondId = $this->makeExercise(['name' => 'Press']);

        $controller = app(ExerciseController::class);
        $req = Request::create("/api/exercises/{$secondId}", 'PUT', [
            'name' => 'Row', // duplicate of first
        ]);

        $this->expectException(ValidationException::class);
        $controller->update($req, $secondId);
    }

    #[Test]
    public function it_update_throws_404_when_not_found(): void
    {
        $controller = app(ExerciseController::class);
        $req = Request::create('/api/exercises/999999', 'PUT', [
            'name' => 'Does not matter',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $controller->update($req, 999999);
    }

    // ---------------- destroy($id) -----------

    #[Test]
    public function it_destroy_deletes_exercise_and_returns_message(): void
    {
        $id = $this->makeExercise();

        $controller = app(ExerciseController::class);
        $res = $controller->destroy($id);

        $data = $res->getData(true);
        $this->assertSame(200, $res->status());
        $this->assertSame('Exercise deleted', $data['message']);

        $this->assertDatabaseMissing('exercises', ['id' => $id]);
    }

    #[Test]
    public function it_destroy_throws_404_when_not_found(): void
    {
        $controller = app(ExerciseController::class);

        $this->expectException(ModelNotFoundException::class);
        $controller->destroy(999999);
    }
}
