<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;


class AuthController extends Controller
{

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

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            event(new Registered($user)); // 🔔 Send verification email

            $token = $user->createToken('API_TOKEN')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user,
                'message' => 'Registered successfully. Please check your email to verify your account.'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();

            if (isset($errors['email'])) {
                return response()->json(['message' => 'Email is already in use'], 422);
            }

            return response()->json(['message' => 'Validation error', 'errors' => $errors], 422);
        }
    }
    
    public function resetPassword(Request $request)
    {
        $request->headers->set('Accept', 'application/json');

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);
                
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->setRememberToken(Str::random(60));

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset successfully!'
            ], 200);
        }

        return response()->json([
            'message' => 'Password reset failed.',
            'reason' => __($status) // Translated message like "This password reset token is invalid."
        ], 422);
    }



    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent']);
        }

        return response()->json(['message' => 'Unable to send reset link'], 500);
    }



}
