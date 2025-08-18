<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Http\Controllers\PaymentMethodController;
use App\Models\PaymentMethod;

class PaymentMethodControllerTest extends TestCase
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

    private function makeMethod(array $overrides = []): int
    {
        $defaults = [
            'name'        => 'Cash ' . uniqid(),
            'description' => 'Pay in cash',
            'active'      => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];

        return DB::table('payment_methods')->insertGetId(array_replace($defaults, $overrides));
    }

    private function req(string $method, string $uri, array $data = []): Request
    {
        $r = Request::create($uri, $method, $data);
        $r->headers->set('Accept', 'application/json');
        return $r;
    }

    // ---------------- index() ----------------

    #[Test]
    public function it_index_returns_sorted_payment_methods(): void
    {
        $id1 = $this->makeMethod(['name' => 'Cash']);
        $id2 = $this->makeMethod(['name' => 'Card']);

        $controller = app(PaymentMethodController::class);

        $res  = $controller->index();    // Collection<PaymentMethod>
        $data = $res->toArray();         // convert to array for assertions

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        // ordered by id ascending
        $this->assertSame([$id1, $id2], array_column($data, 'id'));
    }

    // ---------------- show($id) ---------------

    #[Test]
    public function it_show_returns_single_payment_method(): void
    {
        $id = $this->makeMethod(['name' => 'Bank Transfer']);

        $controller = app(PaymentMethodController::class);
        $res  = $controller->show($id);   // PaymentMethod model
        $data = $res->toArray();

        $this->assertSame($id, $data['id']);
        $this->assertSame('Bank Transfer', $data['name']);
    }

    #[Test]
    public function it_show_throws_404_when_missing(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $controller = app(PaymentMethodController::class);
        $controller->show(999999);
    }

    // ---------------- store() ----------------

    #[Test]
    public function it_store_creates_payment_method(): void
    {
        $controller = app(PaymentMethodController::class);
        $req = $this->req('POST', '/api/payment-methods', [
            'name'        => 'POS',
            'description' => 'Point of sale terminal',
            'active'      => true,
        ]);

        $res  = $controller->store($req);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('POS', $data['name']);
        $this->assertSame('Point of sale terminal', $data['description']);
        $this->assertTrue((bool)$data['active']);

        $this->assertDatabaseHas('payment_methods', [
            'name'   => 'POS',
            'active' => 1,
        ]);
    }

    #[Test]
    public function it_store_returns_422_on_validation_error(): void
    {
        $controller = app(PaymentMethodController::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        // name required -> triggers validator exception
        $req = $this->req('POST', '/api/payment-methods', [
            'description' => 'No name',
        ]);

        $controller->store($req);
    }

    #[Test]
    public function it_store_enforces_unique_name(): void
    {
        $this->makeMethod(['name' => 'UniqueName']);

        $controller = app(PaymentMethodController::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $req = $this->req('POST', '/api/payment-methods', [
            'name' => 'UniqueName',
        ]);

        $controller->store($req);
    }

    // ---------------- update() ----------------

    #[Test]
    public function it_update_edits_fields(): void
    {
        $id = $this->makeMethod(['name' => 'Old', 'description' => 'Old desc', 'active' => 0]);

        $controller = app(PaymentMethodController::class);
        $req = $this->req('PATCH', "/api/payment-methods/{$id}", [
            'name'        => 'New',
            'description' => 'New desc',
            'active'      => true,
        ]);

        $res  = $controller->update($req, $id);
        $data = $res->getData(true);

        $this->assertSame($id, $data['id']);
        $this->assertSame('New', $data['name']);
        $this->assertSame('New desc', $data['description']);
        $this->assertTrue((bool)$data['active']);

        $this->assertDatabaseHas('payment_methods', [
            'id'      => $id,
            'name'    => 'New',
            'active'  => 1,
        ]);
    }

    #[Test]
    public function it_update_throws_404_when_missing(): void
    {
        $controller = app(PaymentMethodController::class);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $req = $this->req('PATCH', '/api/payment-methods/999999', ['name' => 'X']);
        $controller->update($req, 999999);
    }

    #[Test]
    public function it_update_enforces_unique_name_except_self(): void
    {
        $idA = $this->makeMethod(['name' => 'NameA']);
        $idB = $this->makeMethod(['name' => 'NameB']);

        $controller = app(PaymentMethodController::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        // try to rename B to A -> violates unique
        $req = $this->req('PATCH', "/api/payment-methods/{$idB}", ['name' => 'NameA']);
        $controller->update($req, $idB);
    }

    // ---------------- destroy() --------------

    #[Test]
    public function it_destroy_deletes_payment_method_and_returns_message(): void
    {
        $id = $this->makeMethod();

        $controller = app(PaymentMethodController::class);
        $res  = $controller->destroy($id);
        $data = $res->getData(true);

        $this->assertSame(200, $res->status());
        $this->assertSame('Payment method deleted', $data['message']);
        $this->assertDatabaseMissing('payment_methods', ['id' => $id]);
    }

    #[Test]
    public function it_destroy_throws_404_when_missing(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $controller = app(PaymentMethodController::class);
        $controller->destroy(999999);
    }
}
