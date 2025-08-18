<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Http\Controllers\MembershipPlanController;
use App\Models\MembershipPlan;

class MembershipPlanControllerTest extends TestCase
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

    private function makePlan(array $overrides = []): int
    {
        $defaults = [
            'name'          => 'Monthly',
            'description'   => '30 days access',
            'price'         => 50.00,
            'duration_days' => 30,
            'created_at'    => now(),
            'updated_at'    => now(),
        ];

        return DB::table('membership_plans')->insertGetId(array_replace($defaults, $overrides));
    }

    private function makeOfferForPlan(int $planId, array $overrides = []): int
    {
        $defaults = [
            'membership_plan_id' => $planId,
            'title'              => 'New Year Deal',
            'description'        => 'Limited time',
            'discount_amount'    => 10.00,
            'discount_percent'   => null,
            'starts_at'          => null,
            'ends_at'            => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];

        return DB::table('offers')->insertGetId(array_replace($defaults, $overrides));
    }

    private function req(string $method, string $uri, array $data = []): Request
    {
        $r = Request::create($uri, $method, $data);
        $r->headers->set('Accept', 'application/json');
        return $r;
    }

    // ---------------- index() ----------------

    #[Test]
    public function it_index_returns_plans_with_offers(): void
    {
        $p1 = $this->makePlan(['name' => 'Monthly']);
        $p2 = $this->makePlan(['name' => 'Annual', 'duration_days' => 365, 'price' => 450]);

        // Attach a couple of offers to p1
        $this->makeOfferForPlan($p1, ['title' => 'Promo A']);
        $this->makeOfferForPlan($p1, ['title' => 'Promo B']);

        $controller = app(MembershipPlanController::class);
        $res  = $controller->index();
        $data = $res->toArray();


        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        // ensure offers relation present
        $plan1 = collect($data)->firstWhere('id', $p1);
        $this->assertNotNull($plan1);
        $this->assertArrayHasKey('offers', $plan1);
        $this->assertCount(2, $plan1['offers']);

        $plan2 = collect($data)->firstWhere('id', $p2);
        $this->assertNotNull($plan2);
        $this->assertArrayHasKey('offers', $plan2);
        $this->assertCount(0, $plan2['offers']);
    }

    // ---------------- show($id) ---------------

    #[Test]
    public function it_show_returns_single_plan_with_offers(): void
    {
        $id = $this->makePlan(['name' => 'Quarterly', 'duration_days' => 90, 'price' => 140]);
        $this->makeOfferForPlan($id, ['title' => 'Spring']);

        $controller = app(MembershipPlanController::class);
        $res  = $controller->show($id);
        $data = $res->toArray();

        $this->assertSame($id, $data['id']);
        $this->assertSame('Quarterly', $data['name']);
        $this->assertArrayHasKey('offers', $data);
        $this->assertCount(1, $data['offers']);
    }

    #[Test]
    public function it_show_throws_404_when_missing(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $controller = app(MembershipPlanController::class);
        $controller->show(999999);
    }

    // ---------------- store() ----------------

    #[Test]
    public function it_store_creates_plan_with_validation(): void
    {
        $controller = app(MembershipPlanController::class);
        $req = $this->req('POST', '/api/membership-plans', [
            'name'          => 'Bi-Weekly',
            'description'   => '14 days access',
            'price'         => 25.5,
            'duration_days' => 14,
        ]);

        $res  = $controller->store($req);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('Bi-Weekly', $data['name']);
        $this->assertEquals(25.5, (float)$data['price']);
        $this->assertSame(14, $data['duration_days']);

        $this->assertDatabaseHas('membership_plans', [
            'name'          => 'Bi-Weekly',
            'duration_days' => 14,
        ]);
    }

    #[Test]
    public function it_store_throws_422_on_invalid_payload(): void
    {
        $controller = app(MembershipPlanController::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        // Missing required fields -> validate() throws
        $req = $this->req('POST', '/api/membership-plans', [
            'description' => 'No required fields',
        ]);

        $controller->store($req);
    }

    // ---------------- update() ----------------

    #[Test]
    public function it_update_edits_fields(): void
    {
        $id = $this->makePlan(['name' => 'Starter', 'price' => 10, 'duration_days' => 7]);
        $controller = app(MembershipPlanController::class);

        $req  = $this->req('PATCH', "/api/membership-plans/{$id}", [
            'name'          => 'Starter Plus',
            'description'   => 'Updated',
            'price'         => 12.75,
            'duration_days' => 10,
        ]);
        $res  = $controller->update($req, $id);
        $data = $res->getData(true);

        $this->assertSame($id, $data['id']);
        $this->assertSame('Starter Plus', $data['name']);
        $this->assertEquals(12.75, (float)$data['price']);
        $this->assertSame(10, $data['duration_days']);

        $this->assertDatabaseHas('membership_plans', [
            'id'            => $id,
            'name'          => 'Starter Plus',
            'duration_days' => 10,
        ]);
    }

    #[Test]
    public function it_update_throws_404_when_missing(): void
    {
        $controller = app(MembershipPlanController::class);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $req = $this->req('PATCH', '/api/membership-plans/999999', [
            'name' => 'Nope',
        ]);

        $controller->update($req, 999999);
    }

    // ---------------- destroy() --------------

    #[Test]
    public function it_destroy_deletes_plan_and_returns_204(): void
    {
        $id = $this->makePlan();
        $controller = app(MembershipPlanController::class);

        $res = $controller->destroy($id);

        $this->assertSame(204, $res->status());
        $this->assertDatabaseMissing('membership_plans', ['id' => $id]);
    }

    #[Test]
    public function it_destroy_returns_204_even_when_missing(): void
    {
        $controller = app(MembershipPlanController::class);

        $res = $controller->destroy(999999);

        $this->assertSame(204, $res->status());
        // No exception; nothing to assert in DB.
    }
}
