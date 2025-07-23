<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\TeacherSalary;
use App\Models\User;
use Illuminate\Http\JsonResponse;
class TeacherSalaryController extends Controller
{
     public function index()
    {
        return TeacherSalary::with('user')->orderBy('pay_date', 'desc')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'pay_date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ]);

        $salary = TeacherSalary::create($data);

        return response()->json($salary, 201);
    }

    public function show($id)
    {
        $salary = TeacherSalary::with('user')->findOrFail($id);
        return response()->json($salary);
    }

    public function byUser($userId): JsonResponse
    {
        $salaries = TeacherSalary::with('user')
            ->where('user_id', $userId)
            ->orderBy('pay_date', 'desc')
            ->get();

        return response()->json($salaries);
    }

}
