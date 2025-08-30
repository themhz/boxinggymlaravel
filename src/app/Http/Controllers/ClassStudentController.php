<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClassStudentController extends Controller
{
    /**
     * GET /api/classes/{class}/students
     */
    public function index(ClassModel $class): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

         $students = $class->students()
            ->withPivot(['status', 'note'])
            ->get();

        return response()->json([
            'result' => 'success',
            'data'   => $students,
        ]);
    }

    /**
     * GET /api/classes/{class}/students/{student}
     */
    public function show(ClassModel $class, Student $student): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        $enrollment = $class->students()
            ->withPivot(['status', 'note'])
            ->where('students.id', $student->id)
            ->first();

        if (! $enrollment) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Student is not enrolled in this class',
            ], 404);
        }

        return response()->json([
            'result' => 'success',
            'data'   => $enrollment,
        ]);
    }

    /**
     * POST /api/classes/{class}/students
     * Body: { "student_id": 2, "status": "active", "note": "front row" }
     */
    public function store(Request $request, ClassModel $class): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'status'     => 'nullable|string|max:50',
            'note'       => 'nullable|string|max:255',
        ]);

        // prevent duplicate enrollment
        if ($class->students()->where('student_id', $data['student_id'])->exists()) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Student already enrolled in this class',
            ], 409);
        }

        // attach with pivot data
        $class->students()->attach($data['student_id'], [
            'status' => $data['status'] ?? null,
            'note'   => $data['note'] ?? null,
        ]);

        // return ONLY the newly enrolled student (with pivot)
        $enrollment = $class->students()
            ->where('students.id', $data['student_id'])
            ->first();

        return response()->json([
            'result'  => 'success',
            'message' => 'Student enrolled to class',
            'data'    => $enrollment,
        ], 201);
    }

    /**
     * PUT/PATCH /api/classes/{class}/students/{student}
     * Body: { "status": "...", "note": "..." }
     */
    public function update(Request $request, ClassModel $class, Student $student): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'status' => 'nullable|string|max:50',
            'note'   => 'nullable|string|max:255',
        ]);

        if (! $class->students()->where('student_id', $student->id)->exists()) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Student is not enrolled in this class',
            ], 404);
        }

        $class->students()->updateExistingPivot($student->id, $data);

        $pivot = $class->students()
            ->where('students.id', $student->id)
            ->first()
            ?->pivot;

        return response()->json([
            'result'  => 'success',
            'message' => 'Enrollment updated',
            'data'    => $pivot,
        ]);
    }

    /**
     * DELETE /api/classes/{class}/students/{student}
     */
    public function destroy(Request $request, ClassModel $class, Student $student): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        if (! $class->students()->where('student_id', $student->id)->exists()) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Student is not enrolled in this class',
            ], 404);
        }

        $class->students()->detach($student->id);

        return response()->json([
            'result'  => 'success',
            'message' => 'Student removed from class',
        ]);
    }
}
