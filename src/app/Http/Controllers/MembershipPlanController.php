<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use Illuminate\Http\Request;

class MembershipPlanController extends Controller
{
    public function index()
    {
        return MembershipPlan::with('offers')->get();
    }

    public function show($id)
    {
        return MembershipPlan::with('offers')->findOrFail($id);
    }

    public function store(Request $req)
    {
        $plan = MembershipPlan::create($req->validate([
            'name'          => 'required|string',
            'description'   => 'nullable|string',
            'price'         => 'required|numeric',
            'duration_days' => 'required|integer',
        ]));
        return response()->json($plan, 201);
    }

    public function update(Request $req, $id)
    {
        $plan = MembershipPlan::findOrFail($id);
        $plan->update($req->only(['name','description','price','duration_days']));
        return response()->json($plan);
    }

    public function destroy($id)
    {
        MembershipPlan::destroy($id);
        return response()->json(null, 204);
    }
}
