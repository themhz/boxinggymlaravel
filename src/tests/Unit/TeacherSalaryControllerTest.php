<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Http\Controllers\TeacherSalaryController;
use App\Models\Teacher;
use App\Models\TeacherSalary;

class TeacherSalaryControllerTest extends TestCase
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

    private function makeTeacher(array $overrides = []): int
    {
        $defaults = [
            'user_id'    => null,
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

    private function makeSalary(int $teacherId, array $overrides = []): int
    {
        $defaults = [
            'teacher_id' => $teacherId,
            'year'       => 2025,
            'month'      => 8,
            'amount'     => 1000,
            'due_date'   => '2025-08-31',
            'is_paid'    => 0,
            'paid_at'    => null,
            'method'     => 'bank',
            'notes'      => 'Initial',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('teacher_salaries')->insertGetId(array_replace($defaults, $overrides));
    }

    private function req(string $method, string $uri, array $data = []): Request
    {
        $r = Request::create($uri, $method, $data);
        $r->headers->set('Accept', 'application/json');
        return $r;
    }

    // ---------------- index() ----------------

    #[Test]
    public function it_index_returns_salaries_for_teacher(): void
    {
        $teacherId = $this->makeTeacher(['first_name' => 'Alex', 'last_name' => 'Coach']);
        $otherId   = $this->makeTeacher();

        $s1 = $this->makeSalary($teacherId, ['year' => 2025, 'month' => 7]);
        $s2 = $this->makeSalary($teacherId, ['year' => 2025, 'month' => 8]);
        $this->makeSalary($otherId,   ['year' => 2025, 'month' => 8]); // not included

        $controller = app(TeacherSalaryController::class);
        $teacher    = Teacher::findOrFail($teacherId);

        $res  = $controller->index($this->req('GET', "/api/teachers/{$teacherId}/salaries"), $teacher);
        $data = $res->getData(true);

        $this->assertIsArray($data);
        $ids = collect($data)->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$s1, $s2], $ids);
        $this->assertArrayHasKey('teacher', $data[0]); // relation loaded
    }

    // ---------------- store() ----------------

    #[Test]
    public function it_store_creates_salary_when_not_exists(): void
    {
        $teacherId = $this->makeTeacher();

        $controller = app(TeacherSalaryController::class);
        $teacher    = Teacher::findOrFail($teacherId);

        $payload = [
            'year'     => 2025,
            'month'    => 9,
            'amount'   => 1200.50,
            'due_date' => '2025-09-30',
            // is_paid omitted -> default false handled by DB/Model
            'method'   => 'cash',
            'notes'    => 'Sept salary',
        ];

        $res  = $controller->store($this->req('POST', "/api/teachers/{$teacherId}/salaries", $payload), $teacher);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame(2025, $data['year']);
        $this->assertSame(9, $data['month']);
        $this->assertEquals(1200.50, $data['amount']);
        $this->assertArrayHasKey('teacher', $data);

        $this->assertDatabaseHas('teacher_salaries', [
            'teacher_id' => $teacherId,
            'year'       => 2025,
            'month'      => 9,
            'amount'     => 1200.50,
        ]);
    }

    #[Test]
    public function it_store_returns_409_when_duplicate_month_for_teacher(): void
    {
        $teacherId = $this->makeTeacher();
        $this->makeSalary($teacherId, ['year' => 2025, 'month' => 9]);

        $controller = app(TeacherSalaryController::class);
        $teacher    = Teacher::findOrFail($teacherId);

        $res  = $controller->store($this->req('POST', "/api/teachers/{$teacherId}/salaries", [
            'year' => 2025, 'month' => 9, 'amount' => 1000
        ]), $teacher);

        $this->assertSame(409, $res->status());
        $this->assertSame('Salary already exists for this month', $res->getData(true)['message']);
    }

    // ---------------- show() ----------------

    #[Test]
    public function it_show_returns_salary_when_belongs_to_teacher(): void
    {
        $teacherId = $this->makeTeacher();
        $id        = $this->makeSalary($teacherId, ['year' => 2025, 'month' => 10]);

        $controller = app(TeacherSalaryController::class);
        $teacher    = Teacher::findOrFail($teacherId);
        $salary     = TeacherSalary::findOrFail($id);

        $res  = $controller->show($teacher, $salary);
        $data = $res->getData(true);

        $this->assertSame($id, $data['id']);
        $this->assertArrayHasKey('teacher', $data);
    }

    #[Test]
    public function it_show_returns_404_when_salary_not_for_teacher(): void
    {
        $t1 = $this->makeTeacher();
        $t2 = $this->makeTeacher();
        $id = $this->makeSalary($t2, ['year' => 2025, 'month' => 10]);

        $controller = app(TeacherSalaryController::class);
        $teacher    = Teacher::findOrFail($t1);
        $salary     = TeacherSalary::findOrFail($id);

        $res = $controller->show($teacher, $salary);
        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
    }

    // ---------------- update() ----------------

    #[Test]
    public function it_update_edits_fields_and_autosets_paid_at_when_marking_paid(): void
    {
        $teacherId = $this->makeTeacher();
        $id        = $this->makeSalary($teacherId, ['year' => 2025, 'month' => 11, 'is_paid' => 0, 'paid_at' => null]);

        $controller = app(TeacherSalaryController::class);
        $teacher    = Teacher::findOrFail($teacherId);
        $salary     = TeacherSalary::findOrFail($id);

        $res  = $controller->update($this->req('PATCH', "/api/teachers/{$teacherId}/salaries/{$id}", [
            'amount'  => 1500,
            'is_paid' => true, // paid_at omitted -> controller should set now()
            'method'  => 'bank',
            'notes'   => 'Paid',
        ]), $teacher, $salary);

        $data = $res->getData(true);

        $this->assertEquals(1500.0, $data['amount']);
        $this->assertTrue((bool)$data['is_paid']);
        $this->assertNotNull($data['paid_at']);
        $this->assertSame('bank', $data['method']);
        $this->assertSame('Paid', $data['notes']);

        $this->assertDatabaseHas('teacher_salaries', [
            'id'        => $id,
            'amount'    => 1500.0,
            'is_paid'   => 1,
            'method'    => 'bank',
            'notes'     => 'Paid',
        ]);
    }

    #[Test]
    public function it_update_returns_409_when_target_month_already_has_salary(): void
    {
        $teacherId = $this->makeTeacher();
        $idA = $this->makeSalary($teacherId, ['year' => 2025, 'month' => 11]);
        $idB = $this->makeSalary($teacherId, ['year' => 2025, 'month' => 12]);

        $controller = app(TeacherSalaryController::class);
        $teacher    = Teacher::findOrFail($teacherId);
        $salaryB    = TeacherSalary::findOrFail($idB);

        // Try to move salaryB to (2025,11) which already exists as idA
        $res = $controller->update($this->req('PATCH', "/api/teachers/{$teacherId}/salaries/{$idB}", [
            'month' => 11,
        ]), $teacher, $salaryB);

        $this->assertSame(409, $res->status());
        $this->assertSame(
            'Another salary already exists for this teacher in that month/year.',
            $res->getData(true)['message']
        );
    }

    #[Test]
    public function it_update_returns_404_when_salary_not_for_teacher(): void
    {
        $t1 = $this->makeTeacher();
        $t2 = $this->makeTeacher();
        $id = $this->makeSalary($t2, ['year' => 2025, 'month' => 3]);

        $controller = app(TeacherSalaryController::class);
        $teacher    = Teacher::findOrFail($t1);
        $salary     = TeacherSalary::findOrFail($id);

        $res = $controller->update($this->req('PATCH', "/api/teachers/{$t1}/salaries/{$id}", [
            'amount' => 2000,
        ]), $teacher, $salary);

        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
    }

    // ---------------- destroy() ----------------

    #[Test]
    public function it_destroy_deletes_salary_when_owner_matches(): void
    {
        $teacherId = $this->makeTeacher();
        $id        = $this->makeSalary($teacherId, ['year' => 2025, 'month' => 2]);

        $controller = app(TeacherSalaryController::class);
        $teacher    = Teacher::findOrFail($teacherId);
        $salary     = TeacherSalary::findOrFail($id);

        $res = $controller->destroy($this->req('DELETE', "/api/teachers/{$teacherId}/salaries/{$id}"), $teacher, $salary);

        $this->assertSame(200, $res->status());
        $this->assertSame('Teacher salary deleted', $res->getData(true)['message']);
        $this->assertDatabaseMissing('teacher_salaries', ['id' => $id]);
    }

    #[Test]
    public function it_destroy_returns_404_when_salary_not_for_teacher(): void
    {
        $t1 = $this->makeTeacher();
        $t2 = $this->makeTeacher();
        $id = $this->makeSalary($t2);

        $controller = app(TeacherSalaryController::class);
        $teacher    = Teacher::findOrFail($t1);
        $salary     = TeacherSalary::findOrFail($id);

        $res = $controller->destroy($this->req('DELETE', "/api/teachers/{$t1}/salaries/{$id}"), $teacher, $salary);

        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
        $this->assertDatabaseHas('teacher_salaries', ['id' => $id]); // still there
    }
}
