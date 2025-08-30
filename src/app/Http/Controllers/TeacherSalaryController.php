<?php

namespace App\Http\Controllers;

use App\Models\TeacherSalary;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherSalaryController extends Controller
{
    // GET /api/teachers/{teacher}/salaries
    public function index(Request $request, Teacher $teacher): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $rows = $teacher->salaries()
            ->with('teacher:id,first_name,last_name')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return response()->json([
            'result' => 'success',
            'data'   => $rows,
        ]);
    }

    // POST /api/teachers/{teacher}/salaries
    public function store(Request $request, Teacher $teacher): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

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
            ->where('year',  $data['year'])
            ->where('month', $data['month'])
            ->exists();

        if ($exists) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Salary already exists for this month',
            ], 409);
        }

        // auto-set paid_at if marking as paid and not provided
        if (!empty($data['is_paid']) && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        $salary = $teacher->salaries()->create($data)->load('teacher:id,first_name,last_name');

        return response()->json([
            'result'  => 'success',
            'message' => 'Teacher salary created',
            'data'    => $salary,
        ], 201);
    }

    // GET /api/teachers/{teacher}/salaries/{salary}
    public function show(Teacher $teacher, TeacherSalary $salary): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        if ($salary->teacher_id !== $teacher->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Not found',
            ], 404);
        }

        return response()->json([
            'result' => 'success',
            'data'   => $salary->load('teacher:id,first_name,last_name'),
        ]);
    }

    // PUT/PATCH /api/teachers/{teacher}/salaries/{salary}
    public function update(Request $request, Teacher $teacher, TeacherSalary $salary): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        if ($salary->teacher_id !== $teacher->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Not found',
            ], 404);
        }

        $data = $request->validate([
            'year'     => ['sometimes','integer','between:2000,2100'],
            'month'    => ['sometimes','integer','between:1,12'],
            'amount'   => ['sometimes','numeric','min:0'],
            'due_date' => ['sometimes','nullable','date'],
            'is_paid'  => ['sometimes','boolean'],
            'paid_at'  => ['sometimes','nullable','date'],
            'method'   => ['sometimes','nullable','string','max:30'],
            'notes'    => ['sometimes','nullable','string'],
        ]);

        // Determine prospective year/month
        $newYear  = $data['year']  ?? $salary->year;
        $newMonth = $data['month'] ?? $salary->month;

        $exists = TeacherSalary::where('teacher_id', $teacher->id)
            ->where('year', $newYear)
            ->where('month', $newMonth)
            ->where('id', '!=', $salary->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Another salary already exists for this teacher in that month/year.',
            ], 409);
        }

        // auto-set paid_at if marking as paid and not provided
        if (array_key_exists('is_paid', $data) && $data['is_paid'] && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        $salary->update($data);

        return response()->json([
            'result'  => 'success',
            'message' => 'Teacher salary updated',
            'data'    => $salary->fresh()->load('teacher:id,first_name,last_name'),
        ]);
    }

    // DELETE /api/teachers/{teacher}/salaries/{salary}
    public function destroy(Request $request, Teacher $teacher, TeacherSalary $salary): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        if ($salary->teacher_id !== $teacher->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Not found',
            ], 404);
        }

        $salary->delete();

        return response()->json([
            'result'  => 'success',
            'message' => 'Teacher salary deleted',
        ]);
    }
}
