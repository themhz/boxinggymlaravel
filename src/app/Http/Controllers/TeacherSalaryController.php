<?php

// app/Http/Controllers/TeacherSalaryController.php
namespace App\Http\Controllers;

use App\Models\TeacherSalary;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TeacherSalaryController extends Controller
{
    // GET /api/teacher-salaries
    public function index(Request $request, Teacher $teacher): JsonResponse
    {        
        return response()->json(
            $teacher->salaries()->with('teacher:id,first_name,last_name')->get()
        );
    }

    public function store(Request $request, Teacher $teacher): JsonResponse
    {
        $data = $request->validate([
            'year'     => ['required','integer','between:2000,2100'],
            'month'    => ['required','integer','between:1,12'],
            'amount'   => ['required','numeric','min:0'],
            'due_date' => ['nullable','date'],
            'is_paid'  => ['sometimes','boolean'],
            'paid_at'  => ['nullable','date'],
            'method'   => ['nullable','string','max:30'],
            'notes'    => ['nullable','string'],
        ]);

        $exists = $teacher->salaries()
            ->where('year',$data['year'])
            ->where('month',$data['month'])
            ->exists();

        if ($exists) {
            return response()->json(['message'=>'Salary already exists for this month'],409);
        }

        $salary = $teacher->salaries()->create($data);

        return response()->json($salary->load('teacher:id,first_name,last_name'),201);
    }

    public function show(Teacher $teacher, TeacherSalary $salary): JsonResponse
    {
        if ($salary->teacher_id !== $teacher->id) {
            return response()->json(['message'=>'Not found'],404);
        }
        return response()->json($salary->load('teacher:id,first_name,last_name'));
    }

    public function update(Request $request, Teacher $teacher, TeacherSalary $salary): JsonResponse
    {
        if ($salary->teacher_id !== $teacher->id) {
            return response()->json(['message'=>'Not found'],404);
        }

        $data = $request->validate([
            'year'     => ['sometimes','integer','between:2000,2100'],
            'month'    => ['sometimes','integer','between:1,12'],
            'amount'   => ['sometimes','numeric','min:0'],
            'due_date' => ['nullable','date'],
            'is_paid'  => ['sometimes','boolean'],
            'paid_at'  => ['nullable','date'],
            'method'   => ['nullable','string','max:30'],
            'notes'    => ['nullable','string'],
        ]);

        // Determine what year/month we’re about to save
        $newYear  = $data['year']  ?? $salary->year;
        $newMonth = $data['month'] ?? $salary->month;

        $exists = TeacherSalary::where('teacher_id', $teacher->id)
            ->where('year', $newYear)
            ->where('month', $newMonth)
            ->where('id', '!=', $salary->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Another salary already exists for this teacher in that month/year.',
            ], 409);
        }

        // auto-set paid_at if marking as paid and not provided
        if (array_key_exists('is_paid', $data) && $data['is_paid'] && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        $salary->update($data);

        return response()->json($salary->fresh()->load('teacher:id,first_name,last_name'));
    }


    public function destroy(Request $request, Teacher $teacher, TeacherSalary $salary): JsonResponse
    {
        die("ok");
        $request->headers->set('Accept', 'application/json'); // double-force JSON
        if ($salary->teacher_id !== $teacher->id) {
            return response()->json(['message'=>'Not found'],404);
        }

        $salary->delete();

        return response()->json(['message'=>'Teacher salary deleted']);
    }

}
