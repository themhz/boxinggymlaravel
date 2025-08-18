<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\StudentPaymentController;
use App\Models\Student;
use App\Models\StudentPayment;

class StudentPaymentControllerTest extends TestCase
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

    private function makeStudent(array $overrides = []): int
    {
        // create a user first (students usually reference users)
        $userId = DB::table('users')->insertGetId([
            'name'       => 'User '.uniqid(),
            'email'      => 'user_'.uniqid('', true).'@example.com',
            'password'   => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $defaults = [
            'user_id'    => $userId,
            'name'       => $overrides['name'] ?? 'Student '.uniqid(),
            'email'      => $overrides['email'] ?? ('student_'.uniqid('', true).'@example.com'),
            'phone'      => $overrides['phone'] ?? null,
            'dob'        => $overrides['dob'] ?? null,
            'image'      => $overrides['image'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('students')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makePlan(array $overrides = []): int
    {
        $defaults = [
            'name'           => 'Monthly',
            'description'    => null,
            'price'          => 50.0,
            'duration_days'  => 30,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];
        return DB::table('membership_plans')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeOffer(array $overrides = []): int
    {
        $defaults = [
            'membership_plan_id' => null,
            'title'              => 'Promo',
            'description'        => null,
            'discount_amount'    => 5.0,
            'discount_percent'   => null,
            'starts_at'          => null,
            'ends_at'            => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];
        return DB::table('offers')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makePaymentMethod(array $overrides = []): int
    {
        $defaults = [
            'name'       => 'Cash',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return DB::table('payment_methods')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makePayment(array $overrides = []): int
    {
        $defaults = [
            'student_id'        => $this->makeStudent(),
            'membership_plan_id'=> $this->makePlan(),
            'offer_id'          => null,
            'payment_method_id' => $this->makePaymentMethod(),
            'start_date'        => '2025-01-01',
            'end_date'          => '2025-01-31',
            'amount'            => 45.00,
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
        return DB::table('student_payments')->insertGetId(array_replace($defaults, $overrides));
    }

    // ---------- index() ----------

    #[Test]
    public function it_index_lists_payments_for_student_ordered_desc(): void
    {
        $studentA = Student::find($this->makeStudent());
        $studentB = Student::find($this->makeStudent());

        // Two for A, one for B
        $p1 = $this->makePayment(['student_id' => $studentA->id, 'created_at' => now()->subDay()]);
        $p2 = $this->makePayment(['student_id' => $studentA->id, 'created_at' => now()]);
        $p3 = $this->makePayment(['student_id' => $studentB->id]);

        $controller = app(StudentPaymentController::class);
        $res = $controller->index($studentA);

        $data = $res->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        // latest first
        $this->assertSame($p2, $data[0]['id']);
        $this->assertSame($p1, $data[1]['id']);

        // relations present (snake_case)
        $this->assertArrayHasKey('membership_plan', $data[0]);
        $this->assertArrayHasKey('payment_method', $data[0]);
        $this->assertArrayHasKey('offer', $data[0]);
    }

    // ---------- show() ----------

    #[Test]
    public function it_show_returns_single_payment_with_relations(): void
    {
        $student = Student::find($this->makeStudent());
        $plan    = $this->makePlan();
        $offer   = $this->makeOffer();
        $method  = $this->makePaymentMethod();

        $pid = $this->makePayment([
            'student_id'        => $student->id,
            'membership_plan_id'=> $plan,
            'offer_id'          => $offer,
            'payment_method_id' => $method,
        ]);

        $payment   = StudentPayment::findOrFail($pid);
        $controller = app(StudentPaymentController::class);
        $res        = $controller->show($student, $payment);

        $data = $res->getData(true);
        $this->assertSame($pid, $data['id']);
        $this->assertEquals($plan,   $data['membership_plan']['id']);
        $this->assertEquals($offer,  $data['offer']['id']);
        $this->assertEquals($method, $data['payment_method']['id']);
    }

    // ---------- store() ----------

    #[Test]
    public function it_store_creates_payment_for_student(): void
    {
        $student = Student::find($this->makeStudent());
        $plan    = $this->makePlan();
        $method  = $this->makePaymentMethod();
        $offer   = $this->makeOffer();

        $controller = app(StudentPaymentController::class);
        $req = Request::create("/api/students/{$student->id}/payments", 'POST', [
            'membership_plan_id' => $plan,
            'offer_id'           => $offer,
            'payment_method_id'  => $method,
            'start_date'         => '2025-04-01',
            'end_date'           => '2025-04-30',
            'amount'             => 40.00,
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->store($req, $student);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertEquals(40.00, (float)$data['data']['amount']);
        $this->assertEquals($plan,   $data['data']['membership_plan']['id']);
        $this->assertEquals($offer,  $data['data']['offer']['id']);
        $this->assertEquals($method, $data['data']['payment_method']['id']);

        $this->assertDatabaseHas('student_payments', [
            'student_id'        => $student->id,  // ⬅️ CHANGED
            'membership_plan_id'=> $plan,
            'payment_method_id' => $method,
            'amount'            => 40.00,
        ]);
    }

    #[Test]
    public function it_store_returns_422_on_validation_error(): void
    {
        $student = Student::find($this->makeStudent());

        $controller = app(StudentPaymentController::class);
        $req = Request::create("/api/students/{$student->id}/payments", 'POST', [
            'start_date' => '2025-05-10',
            'end_date'   => '2025-05-01', // invalid
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->store($req, $student);
    }

    // ---------- update() ----------

    #[Test]
    public function it_update_edits_payment_when_owner(): void
    {
        $student = Student::find($this->makeStudent());
        $plan    = $this->makePlan();
        $method  = $this->makePaymentMethod();

        $pid = $this->makePayment([
            'student_id'        => $student->id,
            'membership_plan_id'=> $plan,
            'payment_method_id' => $method,
            'amount'            => 45.00,
            'start_date'        => '2025-01-01',
            'end_date'          => '2025-01-31',
        ]);

        $payment = StudentPayment::findOrFail($pid);

        $controller = app(StudentPaymentController::class);
        $req = Request::create("/api/students/{$student->id}/payments/{$pid}", 'PATCH', [
            'amount'   => 50.00,
            'end_date' => '2025-02-15',
        ]);
        $req->headers->set('Accept', 'application/json');

        $res  = $controller->update($req, $student, $payment);
        $data = $res->getData(true);

        $this->assertSame('Payment updated successfully.', $data['message']);
        $this->assertEquals(50.00, (float)$data['data']['amount']);
        $this->assertEquals('2025-02-15', $data['data']['end_date']);

        $this->assertDatabaseHas('student_payments', [
            'id'       => $pid,
            'amount'   => 50.00,
            'end_date' => '2025-02-15',
        ]);
    }

    #[Test]
    public function it_update_returns_404_when_payment_not_owned_by_student(): void
    {
        $owner = Student::find($this->makeStudent());
        $other = Student::find($this->makeStudent());

        $pid = $this->makePayment(['student_id' => $owner->id]);
        $payment = StudentPayment::findOrFail($pid);

        $controller = app(StudentPaymentController::class);
        $req = Request::create("/api/students/{$other->id}/payments/{$pid}", 'PATCH', ['amount' => 55]);
        $req->headers->set('Accept', 'application/json');

        $res = $controller->update($req, $other, $payment);
        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
    }

    #[Test]
    public function it_update_returns_422_on_validation_error(): void
    {
        $student = Student::find($this->makeStudent());
        $pid     = $this->makePayment(['student_id' => $student->id]);
        $payment = StudentPayment::findOrFail($pid);

        $controller = app(StudentPaymentController::class);
        $req = Request::create("/api/students/{$student->id}/payments/{$pid}", 'PATCH', [
            'start_date' => '2025-02-01',
            'end_date'   => '2024-01-01', // invalid
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->update($req, $student, $payment);
    }

    // ---------- destroy() ----------

    #[Test]
    public function it_destroy_deletes_payment_when_owner(): void
    {
        $student = Student::find($this->makeStudent());
        $pid     = $this->makePayment(['student_id' => $student->id]);

        $payment = StudentPayment::findOrFail($pid);

        $controller = app(StudentPaymentController::class);
        $res  = $controller->destroy($student, $payment);
        $data = $res->getData(true);

        $this->assertSame('Payment deleted successfully.', $data['message']);
        $this->assertDatabaseMissing('student_payments', ['id' => $pid]);
    }

    #[Test]
    public function it_destroy_returns_404_when_payment_not_owned_by_student(): void
    {
        $owner = Student::find($this->makeStudent());
        $other = Student::find($this->makeStudent());

        $pid = $this->makePayment(['student_id' => $owner->id]);
        $payment = StudentPayment::findOrFail($pid);

        $controller = app(StudentPaymentController::class);
        $res = $controller->destroy($other, $payment);

        $this->assertSame(404, $res->status());
        $this->assertSame('Not found', $res->getData(true)['message']);
    }
}
