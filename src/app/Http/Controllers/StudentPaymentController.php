<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentPayment;
use App\Models\User;

class StudentPaymentController extends Controller
{
    public function index()
    {
        $payments = StudentPayment::with(['user', 'membershipPlan', 'offer', 'paymentMethod'])->get();
        return response()->json($payments);
    }

    // ✅ All payments of a specific student
    public function byStudent(User $user)
    {
        return StudentPayment::with(['membershipPlan', 'offer', 'paymentMethod'])
            ->where('user_id', $user->id)
            ->get();
    }

    // ✅ A specific payment of a specific student
    public function studentPaymentShow(User $user, StudentPayment $payment)
    {
        if ($payment->user_id !== $user->id) {
            return response()->json(['error' => 'Payment does not belong to this user'], 403);
        }

        return $payment->load(['membershipPlan', 'offer', 'paymentMethod']);
    }
}
