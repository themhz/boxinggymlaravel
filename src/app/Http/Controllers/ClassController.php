<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClassController extends Controller
{
    // GET /api/classes
    public function index(): JsonResponse
    {
        $classes = ClassModel::with(['lesson', 'teachers:id,first_name,last_name,email'])
            ->get()
            ->map(fn ($c) => [
                'id'         => $c->id,
                'day'        => $c->day,
                'start_time' => $c->start_time,
                'end_time'   => $c->end_time,
                'capacity'   => $c->capacity,

                'lesson' => [
                    'id'          => $c->lesson->id,
                    'title'       => $c->lesson->title,
                    'description' => $c->lesson->description,
                    'image'       => $c->lesson->image,
                ],

                // multiple teachers now
                'teachers' => $c->teachers->map(fn ($t) => [
                    'id'    => $t->id,
                    'name'  => trim(($t->first_name ?? '').' '.($t->last_name ?? '')) ?: ($t->name ?? null),
                    'email' => $t->email,
                    'pivot' => [
                        'role'       => $t->pivot->role,
                        'is_primary' => (bool) $t->pivot->is_primary,
                    ],
                ])->values(),
            ]);

        return response()->json(['classes' => $classes]);
    }

    // GET /api/classes/schedule
    public function schedule(): JsonResponse
    {
        $classes = ClassModel::with(['lesson', 'teachers:id,first_name,last_name'])
            ->get();

        $schedule = $classes->groupBy('day')
            ->mapWithKeys(fn ($group, $day) => [
                $day => $group->sortBy('start_time')->map(fn ($c) => [
                    'class'      => $c->lesson->title,
                    'start_time' => $c->start_time,
                    'end_time'   => $c->end_time,
                    'capacity'   => $c->capacity,
                    // join teacher names (may be empty)
                    'teachers'   => $c->teachers->map(
                        fn ($t) => trim(($t->first_name ?? '').' '.($t->last_name ?? ''))
                    )->filter()->values(),
                ])->values(),
            ]);

        return response()->json(['schedule' => $schedule]);
    }

    // GET /api/classes/{id}
    public function show($id): JsonResponse
    {
        $class = ClassModel::with(['lesson', 'teachers:id,first_name,last_name,email'])
            ->findOrFail($id);

        return response()->json(['class' => $class]);
    }

    // POST /api/classes
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lesson_id'  => 'required|exists:lessons,id',
            'start_time' => 'required|date_format:H:i:s',
            'end_time'   => 'required|date_format:H:i:s|after:start_time',
            'day'        => 'required|string',
            'capacity'   => 'required|integer|min:1',
        ]);

        $class = ClassModel::create($validated);

        return response()->json([
            'message' => 'Class created successfully',
            'class'   => $class->load('lesson'),
        ], 201);
    }

    // PUT/PATCH /api/classes/{id}
    public function update(Request $request, $id): JsonResponse
    {
        $class = ClassModel::findOrFail($id);

        $validated = $request->validate([
            'lesson_id'  => 'sometimes|exists:lessons,id',
            'start_time' => 'sometimes|date_format:H:i:s',
            'end_time'   => 'sometimes|date_format:H:i:s|after:start_time',
            'day'        => 'sometimes|string',
            'capacity'   => 'sometimes|integer|min:1',
        ]);

        $class->update($validated);

        return response()->json([
            'message' => 'Class updated successfully',
            'class'   => $class->fresh()->load('lesson'),
        ]);
    }

    // DELETE /api/classes/{id}
    public function destroy($id): JsonResponse
    {
        $class = ClassModel::find($id);

        if (! $class) {
            return response()->json(['deleted' => 0], 404);
        }

        $deleted = $class->delete();

        return response()->json(['deleted' => $deleted ? 1 : 0]);
    }

    // GET /api/classes/{id}/students
    public function students($id): JsonResponse
    {
        $class = ClassModel::findOrFail($id);
        return response()->json($class->students);
    }

    // POST /api/classes/{id}/students
    public function addStudent(Request $request, $id): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $class = ClassModel::findOrFail($id);
        $class->students()->syncWithoutDetaching([$request->student_id]);

        return response()->json(['message' => 'Student added to class']);
    }

    // PATCH /api/classes/{classId}/students/{studentId}
    public function updateStudent(Request $request, $classId, $studentId): JsonResponse
    {
        try {
            $class = ClassModel::findOrFail($classId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['result' => 0, 'message' => 'Class not found'], 404);
        }

        $updated = $class->students()->updateExistingPivot($studentId, [
            'status' => $request->input('status'),
            'note'   => $request->input('note'),
        ]);

        return response()->json([
            'result'  => $updated ? 1 : 0,
            'message' => $updated ? 'Student updated in class' : 'Nothing was updated',
        ]);
    }

    // PATCH (partial) /api/classes/{classId}/students/{studentId}
    public function patchStudent(Request $request, $classId, $studentId): JsonResponse
    {
        try {
            $class = ClassModel::findOrFail($classId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['result' => 0, 'message' => 'Class not found'], 404);
        }

        $data = $request->only(['status', 'note']);
        if (empty($data)) {
            return response()->json(['result' => 0, 'message' => 'No fields provided'], 400);
        }

        $updated = $class->students()->updateExistingPivot($studentId, $data);

        return response()->json([
            'result'  => $updated ? 1 : 0,
            'message' => $updated ? 'Student updated in class' : 'Nothing was updated',
        ]);
    }

    // DELETE /api/classes/{classId}/students/{studentId}
    public function removeStudent($classId, $studentId): JsonResponse
    {
        try {
            $class = ClassModel::findOrFail($classId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['result' => 0, 'message' => 'Class not found'], 404);
        }

        $detached = $class->students()->detach($studentId);

        return response()->json([
            'result'  => $detached ? 1 : 0,
            'message' => $detached ? 'Student removed from class' : 'Student not enrolled or already removed',
        ]);
    }
}
