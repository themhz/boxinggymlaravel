<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OfferController extends Controller
{
    public function index()
    {
        return Offer::with('plan')->orderByDesc('id')->get();
    }

    public function show($id)
    {
        return Offer::with('plan')->findOrFail($id);
    }

    public function store(Request $req)
    {
        $data = $this->validateData($req);

        // exactly one of amount/percent must be provided
        $this->assertOneDiscountField($data);

        $offer = Offer::create($data)->load('plan');
        return response()->json($offer, 201);
    }

    public function update(Request $req, $id)
    {
        $offer = Offer::findOrFail($id);
        $data  = $this->validateData($req, updating: true);

        // if either field present, enforce the exclusivity
        if (array_key_exists('discount_amount',$data) || array_key_exists('discount_percent',$data)) {
            $this->assertOneDiscountField($data + [
                'discount_amount'  => $data['discount_amount']  ?? $offer->discount_amount,
                'discount_percent' => $data['discount_percent'] ?? $offer->discount_percent,
            ]);
        }

        $offer->update($data);
        return response()->json($offer->fresh()->load('plan'));
    }

    public function destroy($id)
    {
        Offer::findOrFail($id)->delete();
        return response()->json(['message' => 'Offer deleted'], 200);
    }

    private function validateData(Request $req, bool $updating = false): array
    {
        $rule = fn($r) => $updating ? ['sometimes', ...$r] : $r;

        return $req->validate([
            'membership_plan_id' => $rule(['nullable','exists:membership_plans,id']),
            'title'              => $rule(['required','string','max:150']),
            'description'        => $rule(['nullable','string']),
            'discount_amount'    => $rule(['nullable','numeric','min:0']),
            'discount_percent'   => $rule(['nullable','numeric','min:1','max:100']),
            'starts_at'          => $rule(['nullable','date']),
            'ends_at'            => $rule(['nullable','date','after_or_equal:starts_at']),
        ]);
    }

    private function assertOneDiscountField(array $data): void
    {
        $hasAmount  = !is_null($data['discount_amount']  ?? null);
        $hasPercent = !is_null($data['discount_percent'] ?? null);

        if ($hasAmount && $hasPercent) {
            throw ValidationException::withMessages([
                'discount_amount'  => ['Provide either discount_amount OR discount_percent, not both.'],
                'discount_percent' => ['Provide either discount_amount OR discount_percent, not both.'],
            ]);
        }

        if (!$hasAmount && !$hasPercent) {
            throw ValidationException::withMessages([
                'discount_amount'  => ['Provide discount_amount or discount_percent.'],
                'discount_percent' => ['Provide discount_amount or discount_percent.'],
            ]);
        }
    }
}
