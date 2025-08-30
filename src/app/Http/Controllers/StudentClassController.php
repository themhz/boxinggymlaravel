<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentClassController extends Controller
{
    // GET /students/{student}/classes
    public function index(Student $student): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        $classes = $student->classes()
            ->with(['lesson','teachers'])
            ->get();

        return response()->json([
            'result' => 'success',
            'data'   => $classes,
        ]);
    }

    // GET /students/{student}/classes/{class}
    public function show(Student $student, ClassModel $class): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        // Load the specific enrollment (ensures pivot is present)
        $enrollment = $student->classes()
            ->with(['lesson','teachers'])
            ->where('class_id', $class->id)
            ->first();

        if (! $enrollment) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Not enrolled in this class',
            ], 404);
        }

        return response()->json([
            'result' => 'success',
            'data'   => $enrollment,
        ]);
    }

    // POST /students/{student}/classes
    public function store(Request $request, Student $student): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'class_id' => 'required|integer|exists:classes,id',
            'status'   => 'nullable|string|max:50',
            'note'     => 'nullable|string|max:255',
        ]);

        // prevent duplicate enrollment
        if ($student->classes()->where('class_id', $data['class_id'])->exists()) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Student already in this class',
            ], 409);
        }

        // attach with pivot data
        $student->classes()->attach($data['class_id'], [
            'status' => $data['status'] ?? null,
            'note'   => $data['note'] ?? null,
        ]);

        // fetch ONLY the newly attached class with relations (and pivot)
        $enrollment = $student->classes()
            ->with(['lesson','teachers'])
            ->where('classes.id', $data['class_id'])
            ->first();

        return response()->json([
            'result'  => 'success',
            'message' => 'Student added to class',
            'data'    => $enrollment,
        ], 201);
    }

    // PUT/PATCH /students/{student}/classes/{class}
    public function update(Request $request, Student $student, ClassModel $class): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'status' => 'nullable|string|max:50',
            'note'   => 'nullable|string|max:255',
        ]);

        if (! $student->classes()->where('class_id', $class->id)->exists()) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Not enrolled in this class',
            ], 404);
        }

        $student->classes()->updateExistingPivot($class->id, $data);

        $pivot = $student->classes()
            ->where('class_id', $class->id)
            ->first()
            ->pivot;

        return response()->json([
            'result'  => 'success',
            'message' => 'Enrollment updated',
            'data'    => $pivot,
        ]);
    }

    // DELETE /students/{student}/classes/{class}
    public function destroy(Request $request, Student $student, ClassModel $class): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        if (! $student->classes()->where('class_id', $class->id)->exists()) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Not enrolled in this class',
            ], 404);
        }

        $student->classes()->detach($class->id);

        return response()->json([
            'result'  => 'success',
            'message' => 'Student removed from class',
        ]);
    }
}
