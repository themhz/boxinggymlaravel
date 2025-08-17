<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;

class PaymentMethodController extends Controller
{
    public function index()
    {
        return PaymentMethod::orderBy('id')->get();
    }

    public function show($id)
    {
        return PaymentMethod::findOrFail($id);
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'name'        => 'required|string|max:100|unique:payment_methods,name',
            'description' => 'nullable|string|max:255',
            'active'      => 'boolean',
        ]);

        $method = PaymentMethod::create($data);
        return response()->json($method, 201);
    }

    public function update(Request $req, $id)
    {
        $method = PaymentMethod::findOrFail($id);

        $data = $req->validate([
            'name'        => 'sometimes|string|max:100|unique:payment_methods,name,' . $method->id,
            'description' => 'nullable|string|max:255',
            'active'      => 'boolean',
        ]);

        $method->update($data);
        return response()->json($method);
    }

    public function destroy($id)
    {
        PaymentMethod::findOrFail($id)->delete();
        return response()->json(['message' => 'Payment method deleted'], 200);
    }
}

