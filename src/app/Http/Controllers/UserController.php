<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        //$users = User::all();
        $users = User::with(['student', 'teacher'])->get();

        return response()->json($users);
    }

    public function show($id)
    {
        //$user = User::findOrFail($id);
        $user = User::with(['student', 'teacher'])->findOrFail($id);

        return response()->json($user);
    }

}
