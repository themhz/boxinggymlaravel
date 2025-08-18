<?php

// app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use AuthorizesRequests;
    
    // GET /api/users (admin only)
    public function index(): JsonResponse
    {
        $this->authorize('manage-users');
        return response()->json(User::all());
    }

    // GET /api/users/{user}
    public function show(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();  // ⬅️ current authenticated user

        if (!$currentUser->can('manage-users') && $currentUser->id !== $user->id) {
            abort(403, 'Forbidden');
        }

        return response()->json($user);
    }

    // POST /api/users (admin only)
    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,user,subscriber', 
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json($user, 201);
    }

    // PATCH /api/users/{user}
    public function update(Request $request, User $user): JsonResponse
    {        
        $currentUser = $request->user();

        // Only admins OR the user himself
        if (!$currentUser->can('manage-users') && $currentUser->id !== $user->id) {
            abort(403, 'Forbidden');
        }

        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:6',
            'role'     => 'sometimes|in:admin,user,subscriber',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // If non-admin is editing themselves, don’t allow role change
        if (!$currentUser->can('manage-users')) {
            unset($data['role']);
        }

        $user->update($data);

        return response()->json($user);
}

    // DELETE /api/users/{user} (admin only)
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('manage-users');
        $user->delete();

        return response()->json(['deleted' => true]);
    }
}
