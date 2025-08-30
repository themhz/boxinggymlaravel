<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\PaymentMethod;

class PaymentMethodController extends Controller
{
    // GET /api/payment-methods
    public function index(): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        $methods = PaymentMethod::orderBy('id')->get();

        return response()->json([
            'result' => 'success',
            'data'   => $methods,
        ]);
    }

    // GET /api/payment-methods/{id}
    public function show($id): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        $method = PaymentMethod::find($id);

        if (! $method) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Payment method not found',
            ], 404);
        }

        return response()->json([
            'result' => 'success',
            'data'   => $method,
        ]);
    }

    // POST /api/payment-methods
    public function store(Request $req): JsonResponse
    {
        $req->headers->set('Accept', 'application/json');

        $data = $req->validate([
            'name'        => 'required|string|max:100|unique:payment_methods,name',
            'description' => 'nullable|string|max:255',
            'active'      => 'boolean',
        ]);

        $method = PaymentMethod::create($data);

        return response()->json([
            'result'  => 'success',
            'message' => 'Payment method created',
            'data'    => $method,
        ], 201);
    }

    // PUT/PATCH /api/payment-methods/{id}
    public function update(Request $req, $id): JsonResponse
    {
        $req->headers->set('Accept', 'application/json');

        $method = PaymentMethod::find($id);

        if (! $method) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Payment method not found',
            ], 404);
        }

        $data = $req->validate([
            'name'        => 'sometimes|string|max:100|unique:payment_methods,name,' . $method->id,
            'description' => 'sometimes|nullable|string|max:255',
            'active'      => 'sometimes|boolean',
        ]);

        $method->update($data);

        return response()->json([
            'result'  => 'success',
            'message' => 'Payment method updated',
            'data'    => $method->fresh(),
        ]);
    }

    // DELETE /api/payment-methods/{id}
    public function destroy($id): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        $method = PaymentMethod::find($id);

        if (! $method) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Payment method not found',
            ], 404);
        }

        $method->delete();

        return response()->json([
            'result'  => 'success',
            'message' => 'Payment method deleted',
        ]);
    }
}
