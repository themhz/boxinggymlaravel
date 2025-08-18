<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Student;
use App\Models\StudentPayment;

class StudentPaymentController extends Controller
{
    // GET /api/students/{student}/payments
    public function index(Student $student): JsonResponse
    {
        $payments = StudentPayment::with(['membershipPlan','offer','paymentMethod'])
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($payments);
    }

    // GET /api/students/{student}/payments/{payment}
    public function show(Student $student, StudentPayment $payment): JsonResponse
    {
        if ($payment->student_id !== $student->id) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($payment->load(['membershipPlan','offer','paymentMethod']));
    }

    // POST /api/students/{student}/payments
    public function store(Request $request, Student $student): JsonResponse
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
            'student_id'         => $student->id,                // ⬅️ CHANGED
            'membership_plan_id' => $data['membership_plan_id'],
            'offer_id'           => $data['offer_id'] ?? null,
            'payment_method_id'  => $data['payment_method_id'],
            'start_date'         => $data['start_date'],
            'end_date'           => $data['end_date'],
            'amount'             => $data['amount'],
        ]);

        return response()->json([
            'message' => 'Payment created successfully.',
            'data'    => $payment->load(['membershipPlan','offer','paymentMethod']),
        ], 201);
    }

    // PUT/PATCH /api/students/{student}/payments/{payment}
    public function update(Request $request, Student $student, StudentPayment $payment): JsonResponse
    {
        if ($payment->student_id !== $student->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'membership_plan_id' => 'sometimes|required|exists:membership_plans,id',
            'offer_id'           => 'sometimes|nullable|exists:offers,id',
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

    // DELETE /api/students/{student}/payments/{payment}
    public function destroy(Student $student, StudentPayment $payment): JsonResponse
    {
        if ($payment->student_id !== $student->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully.']);
    }
}
