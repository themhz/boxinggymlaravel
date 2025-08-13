<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\StudentPayment;
use Illuminate\Http\JsonResponse;

class StudentPaymentController extends Controller
{
    /**
     * GET /api/students/{user}/payments
     * List all payments for a specific user (student).
     */
    public function index(User $user): JsonResponse
    {
        $payments = StudentPayment::with(['membershipPlan','offer','paymentMethod'])
        ->where('user_id', $user->id)
        ->orderByDesc('created_at')
        ->get();
        return response()->json($payments);
    }

    /**
     * GET /api/students/{user}/payments/{payment}
     * Show a specific payment (and ensure it belongs to the user).
     */
    public function show(User $user, StudentPayment $payment): JsonResponse
    {
        return response()->json(
            $payment->load(['membershipPlan','offer','paymentMethod'])
        );

        return response()->json(
            $payment->load(['membership_plan','offer','payment_method'])
        );
    }

    /**
     * POST /api/students/{user}/payments
     * Create a new payment for the user.
     */
    public function store(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'membership_plan_id' => 'required|exists:membership_plans,id',
            'offer_id'           => 'nullable|exists:offers,id',
            'payment_method_id'  => 'required|exists:payment_methods,id',
            'start_date'         => 'required|date',
            'end_date'           => 'required|date|after_or_equal:start_date',
            'amount'             => 'required|numeric|min:0',
        ]);

        $payment = StudentPayment::create([
            'user_id'           => $user->id,
            'membership_plan_id'=> $data['membership_plan_id'],
            'offer_id'          => $data['offer_id'] ?? null,
            'payment_method_id' => $data['payment_method_id'],
            'start_date'        => $data['start_date'],
            'end_date'          => $data['end_date'],
            'amount'            => $data['amount'],
        ]);

        return response()->json([
            'message' => 'Payment created successfully.',
            'data'    => $payment->load(['membershipPlan','offer','paymentMethod']),
        ], 201);
    }

    /**
     * PUT/PATCH /api/students/{user}/payments/{payment}
     * Update an existing payment (partial updates allowed).
     */
    public function update(Request $request, User $user, StudentPayment $payment): JsonResponse
    {
        if ($payment->user_id !== $user->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'membership_plan_id' => 'sometimes|required|exists:membership_plans,id',
            'offer_id'           => 'nullable|exists:offers,id',
            'payment_method_id'  => 'sometimes|required|exists:payment_methods,id',
            'start_date'         => 'sometimes|required|date',
            'end_date'           => 'sometimes|required|date|after_or_equal:start_date',
            'amount'             => 'sometimes|required|numeric|min:0',
        ]);

        $payment->fill($data)->save();

        return response()->json([
            'message' => 'Payment updated successfully.',
            'data'    => $payment->fresh()->load(['membershipPlan','offer','paymentMethod']),
        ]);
    }

    /**
     * DELETE /api/students/{user}/payments/{payment}
     * Delete a payment (ownership check).
     */
    public function destroy(User $user, StudentPayment $payment): JsonResponse
    {
        if ($payment->user_id !== $user->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully.']);
    }
}
