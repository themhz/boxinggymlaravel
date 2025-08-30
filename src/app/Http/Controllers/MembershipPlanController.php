<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MembershipPlanController extends Controller
{
    // GET /api/membership-plans
    public function index(): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        $plans = MembershipPlan::with('offers')->get();

        return response()->json([
            'result' => 'success',
            'data'   => $plans,
        ]);
    }

    // GET /api/membership-plans/{id}
    public function show($id): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        $plan = MembershipPlan::with('offers')->find($id);
        if (! $plan) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Membership plan not found',
            ], 404);
        }

        return response()->json([
            'result' => 'success',
            'data'   => $plan,
        ]);
    }

    // POST /api/membership-plans
    public function store(Request $req): JsonResponse
    {
        $req->headers->set('Accept', 'application/json');

        $data = $req->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'price'         => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
        ]);

        $plan = MembershipPlan::create($data)->load('offers');

        return response()->json([
            'result'  => 'success',
            'message' => 'Membership plan created',
            'data'    => $plan,
        ], 201);
    }

    // PUT/PATCH /api/membership-plans/{id}
    public function update(Request $req, $id): JsonResponse
    {
        $req->headers->set('Accept', 'application/json');

        $plan = MembershipPlan::find($id);
        if (! $plan) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Membership plan not found',
            ], 404);
        }

        $data = $req->validate([
            'name'          => 'sometimes|required|string|max:255',
            'description'   => 'sometimes|nullable|string',
            'price'         => 'sometimes|required|numeric|min:0',
            'duration_days' => 'sometimes|required|integer|min:1',
        ]);

        $plan->update($data);

        return response()->json([
            'result'  => 'success',
            'message' => 'Membership plan updated',
            'data'    => $plan->fresh()->load('offers'),
        ]);
    }

    // DELETE /api/membership-plans/{id}
    public function destroy($id): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        $plan = MembershipPlan::find($id);
        if (! $plan) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Membership plan not found',
            ], 404);
        }

        $plan->delete();

        return response()->json([
            'result'  => 'success',
            'message' => 'Membership plan deleted',
        ]);
    }
}
