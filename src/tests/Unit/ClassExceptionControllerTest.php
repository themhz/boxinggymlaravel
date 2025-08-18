<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Controllers\ClassExceptionController;
use App\Models\ClassException;
use App\Models\ClassModel;

class ClassExceptionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard(); // avoid MassAssignment issues in tests
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    // ---------------- Helpers ----------------

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

    private function makeException(array $overrides = []): int
    {
        $defaults = [
            'class_id'            => $this->makeClass(),
            'exception_date'      => '2025-05-01',
            'is_cancelled'        => false,
            'override_start_time' => null,
            'override_end_time'   => null,
            'reason'              => null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ];
        return DB::table('class_exceptions')->insertGetId(array_replace($defaults, $overrides));
    }

    // ---------------- index() ----------------

    #[Test]
    public function it_index_returns_exceptions_with_class_and_lesson(): void
    {
        $classId = $this->makeClass();
        $ex1 = $this->makeException(['class_id' => $classId, 'exception_date' => '2025-06-01']);
        $ex2 = $this->makeException(['class_id' => $classId, 'exception_date' => '2025-04-01']);
        // another, different class
        $this->makeException();

        $controller = app(ClassExceptionController::class);
        $res = $controller->index();

        $data = $res->getData(true);
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(2, count($data));

        // sorted desc by exception_date: first should be 2025-06-01
        $this->assertStringStartsWith('2025-06-01', $data[0]['exception_date']);

        // Ensure nested relations exist
        $this->assertArrayHasKey('class', $data[0]);
        $this->assertArrayHasKey('lesson', $data[0]['class']);
    }

    // ---------------- show() ----------------

    #[Test]
    public function it_show_returns_single_exception_with_relations(): void
    {
        $id = $this->makeException(['exception_date' => '2025-07-10']);

        $controller = app(ClassExceptionController::class);
        $res = $controller->show($id);

        $payload = $res->getData(true);
        $this->assertSame($id, $payload['id']);
        $this->assertArrayHasKey('class', $payload);
        $this->assertArrayHasKey('lesson', $payload['class']);
    }

    #[Test]
    public function it_show_returns_404_when_not_found(): void
    {
        $controller = app(ClassExceptionController::class);
        $res = $controller->show(999999);

        $this->assertSame(404, $res->status());
        $this->assertSame('Exception not found.', $res->getData(true)['message']);
    }

    // ---------------- store() ----------------

    #[Test]
    public function it_store_creates_exception_and_defaults_is_cancelled_false(): void
    {
        $classId = $this->makeClass();

        $controller = app(ClassExceptionController::class);
        $req = Request::create('/api/classes/exceptions', 'POST', [
            'class_id'            => $classId,
            'exception_date'      => '2025-08-15',
            // store() validation accepts H:i (no seconds)
            'override_start_time' => '17:30',
            'override_end_time'   => '18:30',
            'reason'              => 'Public holiday',
            // omit is_cancelled to test default false
        ]);
        $req->headers->set('Accept', 'application/json');

        $res = $controller->store($req);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('Class exception created.', $data['message']);

        // Returned exception data
        $ex = $data['exception'];
        $this->assertSame($classId, $ex['class_id']);
        $this->assertStringStartsWith('2025-08-15', $ex['exception_date']);
        $this->assertIsBool($ex['is_cancelled']);
        $this->assertFalse($ex['is_cancelled']);
        $this->assertSame('17:30', $ex['override_start_time']);
        $this->assertSame('18:30', $ex['override_end_time']);
        $this->assertSame('Public holiday', $ex['reason']);

        $this->assertDatabaseHas('class_exceptions', [
            'class_id'       => $classId,
            'exception_date' => '2025-08-15',
            'is_cancelled'   => 0,
            'reason'         => 'Public holiday',
        ]);
    }

    #[Test]
    public function it_store_returns_422_on_validation_error(): void
    {
        $controller = app(ClassExceptionController::class);
        // missing class_id
        $req = Request::create('/api/classes/exceptions', 'POST', [
            'exception_date' => '2025-08-15',
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->store($req);
    }

    // ---------------- update() ----------------

    #[Test]
    public function it_update_updates_fields_and_casts_is_cancelled_bool(): void
    {
        $id = $this->makeException([
            'exception_date'      => '2025-09-01',
            'is_cancelled'        => false,
            'override_start_time' => '17:00:00',
            'override_end_time'   => '18:00:00',
            'reason'              => 'Old reason',
        ]);

        $controller = app(ClassExceptionController::class);
        $model      = ClassException::findOrFail($id);

        // update() validation expects H:i:s (seconds)
        $req = Request::create("/api/classes/exceptions/{$id}", 'PATCH', [
            'is_cancelled'        => true,
            'override_start_time' => '16:30:00',
            'override_end_time'   => '17:30:00',
            'reason'              => 'Updated reason',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res = $controller->update($req, $model);
        $data = $res->getData(true);

        $this->assertSame('Class exception updated.', $data['message']);
        $this->assertIsBool($data['data']['is_cancelled']);
        $this->assertTrue($data['data']['is_cancelled']);
        $this->assertSame('16:30:00', $data['data']['override_start_time']);
        $this->assertSame('17:30:00', $data['data']['override_end_time']);
        $this->assertSame('Updated reason', $data['data']['reason']);

        $this->assertDatabaseHas('class_exceptions', [
            'id'                 => $id,
            'is_cancelled'       => 1,
            'override_start_time'=> '16:30:00',
            'override_end_time'  => '17:30:00',
            'reason'             => 'Updated reason',
        ]);
    }

    #[Test]
    public function it_update_returns_422_on_validation_error(): void
    {
        $id    = $this->makeException();
        $model = ClassException::findOrFail($id);

        $controller = app(ClassExceptionController::class);
        // invalid time format for update() which expects H:i:s
        $req = Request::create("/api/classes/exceptions/{$id}", 'PATCH', [
            'override_start_time' => '16:30', // missing seconds
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->update($req, $model);
    }

    // ---------------- destroy() ----------------

    #[Test]
    public function it_destroy_deletes_exception(): void
    {
        $id = $this->makeException();

        $controller = app(ClassExceptionController::class);
        $res = $controller->destroy($id);

        $this->assertSame(200, $res->status());
        $this->assertSame('Exception deleted.', $res->getData(true)['message']);
        $this->assertDatabaseMissing('class_exceptions', ['id' => $id]);
    }

    #[Test]
    public function it_destroy_returns_404_when_missing(): void
    {
        $controller = app(ClassExceptionController::class);
        $res = $controller->destroy(999999);

        $this->assertSame(404, $res->status());
        $this->assertSame('Exception not found.', $res->getData(true)['message']);
    }
}
