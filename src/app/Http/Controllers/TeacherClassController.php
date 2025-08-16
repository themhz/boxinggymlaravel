<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;

class TeacherClassController extends Controller
{
    // GET /api/teachers/{teacher}/classes
    public function index(Teacher $teacher): JsonResponse
    {
        $classes = $teacher->classes()
            ->with(['lesson', 'teachers:id,first_name,last_name']) // optional: see who else teaches it
            ->orderByDesc('class_teacher.created_at')              // order by pivot created time
            ->get();

        return response()->json($classes);
    }

    // GET /api/teachers/{teacher}/classes/{class}
    public function show(Teacher $teacher, ClassModel $class): JsonResponse
    {
        if (! $teacher->classes()->whereKey($class->id)->exists()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(
            $class->load(['lesson', 'teachers:id,first_name,last_name'])
        );
    }

    // POST /api/teachers/{teacher}/classes  (attach to existing class)
    public function store(Request $request, Teacher $teacher): JsonResponse
    {
        $data = $request->validate([
            'class_id'   => 'required|exists:classes,id',
            'role'       => 'nullable|string|max:30',
            'is_primary' => 'sometimes|boolean',
        ]);

        // App-level guard (fast path)
        $already = $teacher->classes()->where('classes.id', $data['class_id'])->exists();
        if ($already) {
            return response()->json([
                'message' => 'This teacher is already attached to that class.',
                'errors'  => [
                    'class_id' => ['Duplicate assignment: teacher already in this class.']
                ],
            ], 409); // HTTP 409 Conflict
        }

        // Attach (prefer attach over syncWithoutDetaching for clarity)
        try {
            $teacher->classes()->attach($data['class_id'], [
                'role'       => $data['role'] ?? null,
                'is_primary' => $data['is_primary'] ?? false,
            ]);
        } catch (QueryException $e) {
            // DB-level unique index fallback (race condition safety)
            if ($e->getCode() === '23000') { // integrity constraint violation
                return response()->json([
                    'message' => 'This teacher is already attached to that class.',
                    'errors'  => [
                        'class_id' => ['Duplicate assignment: teacher already in this class.']
                    ],
                ], 409);
            }
            throw $e;
        }

        $class = ClassModel::with(['lesson','teachers:id,first_name,last_name'])->find($data['class_id']);

        return response()->json([
            'message' => 'Teacher attached to class.',
            'data'    => $class,
        ], 201);
    }

    // PUT/PATCH /api/teachers/{teacher}/classes/{class}  (update pivot)
    public function update(Request $request, Teacher $teacher, ClassModel $class): JsonResponse
    {
        $data = $request->validate([
            'role'       => 'nullable|string|max:30',
            'is_primary' => 'sometimes|boolean',
        ]);

        if (! $teacher->classes()->where('classes.id', $class->id)->exists()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $teacher->classes()->updateExistingPivot($class->id, [
            'role'       => $data['role'] ?? null,
            'is_primary' => $data['is_primary'] ?? false,
        ]);

        return response()->json([
            'message' => 'Pivot updated.',
            'data'    => $class->load(['lesson','teachers:id,first_name,last_name']),
        ]);
    }


    // DELETE /api/teachers/{teacher}/classes/{class}  (detach)
    public function destroy(Teacher $teacher, ClassModel $class): JsonResponse
    {
        $teacher->classes()->detach($class->id);

        return response()->json(['message' => 'Teacher detached from class.']);
    }
}
