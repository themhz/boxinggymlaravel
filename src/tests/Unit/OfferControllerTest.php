<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\OfferController;

class OfferControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // allow Offer::create / ->update without fillable noise in tests
        Model::unguard();
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    // ---------------- Helpers ----------------

    private function makeOffer(array $overrides = []): int
    {
        // Provide exactly one of amount/percent by default.
        $defaults = [
            'membership_plan_id' => null,
            'title'              => 'New Year Promo',
            'description'        => 'Limited time',
            'discount_amount'    => 10.00,
            'discount_percent'   => null,
            'starts_at'          => null,
            'ends_at'            => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];

        // Ensure exclusivity if caller overrides both/null—prefer caller intent.
        $data = array_replace($defaults, $overrides);

        // If both set, keep as-is (some tests may need invalid state).
        // If neither set, default to amount=0 to satisfy NOT NULL (if any).
        if (is_null($data['discount_amount']) && is_null($data['discount_percent'])) {
            $data['discount_amount'] = 0;
        }

        return DB::table('offers')->insertGetId($data);
    }

    // --------------- index() -----------------

    #[Test]
    public function it_index_returns_offers_with_plan_ordered_desc(): void
    {
        $first  = $this->makeOffer(['title' => 'Old', 'discount_amount' => 5]);
        $second = $this->makeOffer(['title' => 'New', 'discount_amount' => 7]); // higher id

        $controller = app(OfferController::class);
        $res = $controller->index(); // returns a Collection with 'plan' eager loaded

        $data = $res->toArray();
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        // orderByDesc('id') => latest first
        $this->assertSame($second, $data[0]['id']);
        $this->assertSame($first,  $data[1]['id']);

        // relation key present (may be null)
        $this->assertArrayHasKey('plan', $data[0]);
    }

    // --------------- show($id) ---------------

    #[Test]
    public function it_show_returns_single_offer_with_plan(): void
    {
        $id = $this->makeOffer(['title' => 'Spring Promo', 'discount_percent' => null, 'discount_amount' => 15]);

        $controller = app(OfferController::class);
        $res = $controller->show($id); // returns Model instance (not Response)

        $data = $res->toArray();
        $this->assertSame($id, $data['id']);
        $this->assertSame('Spring Promo', $data['title']);
        $this->assertArrayHasKey('plan', $data); // eager-loaded (can be null)
    }

    #[Test]
    public function it_show_throws_404_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $controller = app(OfferController::class);
        $controller->show(999999);
    }

    // --------------- store(Request) ----------

    #[Test]
    public function it_store_creates_offer_with_amount_only(): void
    {
        $controller = app(OfferController::class);
        $req = Request::create('/api/offers', 'POST', [
            'title'            => 'Amount Only',
            'discount_amount'  => 12.5,
            'discount_percent' => null,
            'starts_at'        => '2025-01-01',
            'ends_at'          => '2025-02-01',
        ]);

        $res  = $controller->store($req);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('Amount Only', $data['title']);
        $this->assertSame(12.5, (float)$data['discount_amount']);
        $this->assertNull($data['discount_percent']);

        $this->assertDatabaseHas('offers', [
            'title'           => 'Amount Only',
            'discount_amount' => 12.5,
            'discount_percent'=> null,
        ]);
    }

    #[Test]
    public function it_store_creates_offer_with_percent_only(): void
    {
        $controller = app(OfferController::class);
        $req = Request::create('/api/offers', 'POST', [
            'title'            => 'Percent Only',
            'discount_amount'  => null,
            'discount_percent' => 25,
            'starts_at'        => '2025-03-01',
            'ends_at'          => '2025-03-31',
        ]);

        $res  = $controller->store($req);
        $data = $res->getData(true);

        $this->assertSame(201, $res->status());
        $this->assertSame('Percent Only', $data['title']);
        $this->assertSame(25.0, (float)$data['discount_percent']);
        $this->assertNull($data['discount_amount']);

        $this->assertDatabaseHas('offers', [
            'title'            => 'Percent Only',
            'discount_amount'  => null,
            'discount_percent' => 25,
        ]);
    }

    #[Test]
    public function it_store_fails_when_both_amount_and_percent_provided(): void
    {
        $controller = app(OfferController::class);
        $req = Request::create('/api/offers', 'POST', [
            'title'            => 'Bad Offer',
            'discount_amount'  => 10,
            'discount_percent' => 20, // both -> invalid
        ]);

        $this->expectException(ValidationException::class);
        $controller->store($req);
    }

    #[Test]
    public function it_store_fails_when_neither_amount_nor_percent_provided(): void
    {
        $controller = app(OfferController::class);
        $req = Request::create('/api/offers', 'POST', [
            'title' => 'No Discount',
        ]);

        $this->expectException(ValidationException::class);
        $controller->store($req);
    }

    #[Test]
    public function it_store_fails_when_ends_before_starts(): void
    {
        $controller = app(OfferController::class);
        $req = Request::create('/api/offers', 'POST', [
            'title'            => 'Date Rule',
            'discount_amount'  => 5,
            'starts_at'        => '2025-05-10',
            'ends_at'          => '2025-05-01', // before -> invalid
        ]);

        $this->expectException(ValidationException::class);
        $controller->store($req);
    }

    // --------------- update(Request, $id) ----

    #[Test]
    public function it_update_can_switch_from_amount_to_percent(): void
    {
        $id = $this->makeOffer([
            'title'            => 'Switch Me',
            'discount_amount'  => 8,
            'discount_percent' => null,
        ]);

        $controller = app(OfferController::class);
        $req = Request::create("/api/offers/{$id}", 'PUT', [
            'discount_amount'  => null, // clear amount
            'discount_percent' => 30,   // set percent
            'title'            => 'Switched',
        ]);

        $res  = $controller->update($req, $id);
        $data = $res->getData(true);

        $this->assertSame('Switched', $data['title']);
        $this->assertSame(30.0, (float)$data['discount_percent']);
        $this->assertNull($data['discount_amount']);

        $this->assertDatabaseHas('offers', [
            'id'               => $id,
            'title'            => 'Switched',
            'discount_amount'  => null,
            'discount_percent' => 30,
        ]);
    }

    #[Test]
    public function it_update_fails_when_both_amount_and_percent_present(): void
    {
        $id = $this->makeOffer([
            'title'            => 'Keep Me',
            'discount_amount'  => 12,
            'discount_percent' => null,
        ]);

        $controller = app(OfferController::class);
        $req = Request::create("/api/offers/{$id}", 'PUT', [
            'discount_amount'  => 5,
            'discount_percent' => 10, // both -> invalid
        ]);

        $this->expectException(ValidationException::class);
        $controller->update($req, $id);
    }

    #[Test]
    public function it_update_fails_when_ends_before_starts(): void
    {
        $id = $this->makeOffer(['title' => 'Dates']);

        $controller = app(OfferController::class);
        $req = Request::create("/api/offers/{$id}", 'PUT', [
            'starts_at' => '2025-06-10',
            'ends_at'   => '2025-06-01', // invalid
        ]);

        $this->expectException(ValidationException::class);
        $controller->update($req, $id);
    }

    #[Test]
    public function it_update_throws_404_when_not_found(): void
    {
        $controller = app(OfferController::class);
        $req = Request::create('/api/offers/999999', 'PUT', [
            'title'           => 'Does not matter',
            'discount_amount' => 3,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $controller->update($req, 999999);
    }

    // --------------- destroy($id) -----------

    #[Test]
    public function it_destroy_deletes_offer_and_returns_message(): void
    {
        $id = $this->makeOffer();

        $controller = app(OfferController::class);
        $res  = $controller->destroy($id);
        $data = $res->getData(true);

        $this->assertSame(200, $res->status());
        $this->assertSame('Offer deleted', $data['message']);

        $this->assertDatabaseMissing('offers', ['id' => $id]);
    }

    #[Test]
    public function it_destroy_throws_404_when_not_found(): void
    {
        $controller = app(OfferController::class);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $controller->destroy(999999);
    }
}
