<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Controllers\ClassSessionController;
use App\Models\User;
use App\Models\ClassModel;



class ClassSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard();

        Gate::define('manage-class-sessions', function (User $user = null) {
            return $user && ($user->is_admin ?? false);
        });
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    // ---------------- helpers ----------------

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

    private function actingAdmin(): void
    {
        $admin = \App\Models\User::find($this->makeUser());
        $admin->setAttribute('is_admin', true);
        $this->actingAs($admin);
    }

    // ---------------- index() ----------------

    #[Test]
    public function it_index_lists_sessions_for_class_with_exercises_key(): void
    {
        $classId = $this->makeClass();
        $s1 = $this->makeSession(['class_id' => $classId, 'date' => '2025-02-01']);
        $s2 = $this->makeSession(['class_id' => $classId, 'date' => '2025-03-01']);

        // other class session should not be returned
        $this->makeSession();

        $controller = app(ClassSessionController::class);
        $class      = \App\Models\ClassModel::findOrFail($classId);

        // index() returns an Eloquent Collection (not a JsonResponse)
        $resCollection = $controller->index($class);
        $data = $resCollection->toArray();

        $this->assertIsArray($data);
        $ids = collect($data)->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$s1, $s2], $ids);

        // exercises relation is loaded (may be empty array)
        $this->assertArrayHasKey('exercises', $data[0]);
    }

    // ---------------- show() ----------------

    #[Test]
    public function it_show_returns_single_session_with_exercises(): void
    {
        $classId = $this->makeClass();
        $sid     = $this->makeSession(['class_id' => $classId, 'date' => '2025-04-02']);

        $controller = app(ClassSessionController::class);
        $class      = ClassModel::findOrFail($classId);

        // show() returns a Model instance (not a JsonResponse)
        $model = $controller->show($class, $sid);
        $data  = $model->toArray();

        $this->assertSame($sid, $data['id']);
        $this->assertArrayHasKey('exercises', $data);
    }

    #[Test]
    public function it_show_throws_404_when_not_found(): void
    {
        $classId = $this->makeClass();
        $controller = app(ClassSessionController::class);
        $class      = ClassModel::findOrFail($classId);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $controller->show($class, 999999);
    }

    // ---------------- store() ----------------

    #[Test]
    public function it_store_creates_session_with_valid_payload(): void
    {
        $classId = $this->makeClass();

        $controller = app(ClassSessionController::class);
        $req = Request::create("/api/classes/{$classId}/sessions", 'POST', [
            'date'       => '2025-05-10',
            'start_time' => '08:00:00',
            'end_time'   => '09:00:00',
            'notes'      => 'Morning session',
        ]);
        $req->headers->set('Accept', 'application/json');

        // store() returns a JsonResponse
        $res  = $controller->store($req, $classId);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());

        // date will be serialized as RFC3339 string (e.g., 2025-05-10T00:00:00.000000Z)
        $this->assertStringStartsWith('2025-05-10', $data['date']);
        $this->assertStringStartsWith('08:00', $data['start_time']);
        $this->assertStringStartsWith('09:00', $data['end_time']);
        $this->assertEquals('Morning session', $data['notes']);

        $this->assertDatabaseHas('class_sessions', [
            'class_id'   => $classId,
            'start_time' => '08:00:00',
            'end_time'   => '09:00:00',
        ]);
    }

    #[Test]
    public function it_store_returns_422_on_validation_error(): void
    {
        $classId = $this->makeClass();

        $controller = app(ClassSessionController::class);
        $req = Request::create("/api/classes/{$classId}/sessions", 'POST', [
            'date'       => '2025-05-10',
            'start_time' => '10:00:00',
            'end_time'   => '09:00:00',
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->store($req, $classId);
    }

    // ---------------- update() ----------------

    #[Test]
    public function it_update_requires_gate(): void
    {
        $classId = $this->makeClass();
        $sid     = $this->makeSession(['class_id' => $classId]);

        $controller = app(ClassSessionController::class);
        $class      = ClassModel::findOrFail($classId);
        $req = Request::create("/api/classes/{$classId}/sessions/{$sid}", 'PATCH', [
            'notes' => 'changed',
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(AuthorizationException::class);
        $controller->update($req, $class, $sid);
    }

    #[Test]
    public function it_update_updates_notes_when_admin(): void
    {
        $this->actingAdmin();

        $classId = $this->makeClass();
        $sid     = $this->makeSession(['class_id' => $classId, 'notes' => 'old']);

        $controller = app(ClassSessionController::class);
        $class      = ClassModel::findOrFail($classId);
        $req = Request::create("/api/classes/{$classId}/sessions/{$sid}", 'PATCH', [
            'notes' => 'updated notes',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->update($req, $class, $sid);
        $data = $res->getData(true);

        $this->assertSame('updated notes', $data['notes']);

        $this->assertDatabaseHas('class_sessions', [
            'id'    => $sid,
            'notes' => 'updated notes',
        ]);
    }

    #[Test]
    public function it_update_returns_404_when_session_missing(): void
    {
        $this->actingAdmin();

        $classId = $this->makeClass();
        $controller = app(ClassSessionController::class);
        $class      = ClassModel::findOrFail($classId);
        $req = Request::create("/api/classes/{$classId}/sessions/999999", 'PATCH', [
            'notes' => 'x',
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $controller->update($req, $class, 999999);
    }

    #[Test]
    public function it_update_returns_422_when_notes_too_long(): void
    {
        $this->actingAdmin();

        $classId = $this->makeClass();
        $sid     = $this->makeSession(['class_id' => $classId]);

        $controller = app(ClassSessionController::class);
        $class      = ClassModel::findOrFail($classId);

        $tooLong = str_repeat('a', 501);
        $req = Request::create("/api/classes/{$classId}/sessions/{$sid}", 'PATCH', [
            'notes' => $tooLong,
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->update($req, $class, $sid);
    }

    // ---------------- destroy() ----------------

    #[Test]
    public function it_destroy_requires_gate(): void
    {
        $classId = $this->makeClass();
        $sid     = $this->makeSession(['class_id' => $classId]);

        $controller = app(ClassSessionController::class);
        $class      = ClassModel::findOrFail($classId);

        $this->expectException(AuthorizationException::class);
        $controller->destroy($class, $sid);
    }

    #[Test]
    public function it_destroy_deletes_session_and_returns_204_when_admin(): void
    {
        $this->actingAdmin();

        $classId = $this->makeClass();
        $sid     = $this->makeSession(['class_id' => $classId]);

        $controller = app(ClassSessionController::class);
        $class      = ClassModel::findOrFail($classId);

        $res = $controller->destroy($class, $sid);

        $this->assertSame(204, $res->status());
        $this->assertDatabaseMissing('class_sessions', ['id' => $sid]);
    }

    #[Test]
    public function it_destroy_returns_404_when_missing_and_admin(): void
    {
        $this->actingAdmin();

        $classId = $this->makeClass();
        $controller = app(ClassSessionController::class);
        $class      = ClassModel::findOrFail($classId);

        $this->expectException(ModelNotFoundException::class);
        $controller->destroy($class, 999999);
    }
}
