<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // public function login(Request $request)
    // {
    //     $credentials = $request->only('email', 'password');

    //     if (!Auth::attempt($credentials)) {
    //         return response()->json(['message' => 'Invalid credentials'], 401);
    //     }

    //     $user = $request->user();
    //     $token = $user->createToken('login-token')->plainTextToken;

    //     return response()->json([
    //         'token' => $token,
    //         'user' => $user
    //     ]);
    // }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        return response()->json([
            'token' => $request->user()->createToken('API_TOKEN')->plainTextToken
        ]);
    }

    public function logout(Request $request)
    {
        // For web logout
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // For API token revocation
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
