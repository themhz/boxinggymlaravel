<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index()
    {
        return Offer::with('plan')->get();
    }

    public function show($id)
    {
        return Offer::with('plan')->findOrFail($id);
    }

    public function store(Request $req)
    {
        $offer = Offer::create($req->validate([
            'membership_plan_id' => 'nullable|exists:membership_plans,id',
            'title'              => 'required|string',
            'description'        => 'nullable|string',
            'discount_amount'    => 'nullable|numeric',
            'discount_percent'   => 'nullable|numeric',
            'starts_at'          => 'nullable|date',
            'ends_at'            => 'nullable|date',
        ]));
        return response()->json($offer, 201);
    }

    public function update(Request $req, $id)
    {
        $offer = Offer::findOrFail($id);
        $offer->update($req->only([
            'membership_plan_id','title','description',
            'discount_amount','discount_percent','starts_at','ends_at'
        ]));
        return response()->json($offer);
    }

    public function destroy($id)
    {
        Offer::destroy($id);
        return response()->json(null, 204);
    }
}
