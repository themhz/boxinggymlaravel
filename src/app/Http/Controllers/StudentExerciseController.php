<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentExercise;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

class StudentExerciseController extends Controller
{
    // GET /students/{student}/exercises
    public function index(Student $student): JsonResponse
    {
        $items = StudentExercise::with('exercise')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($items);
    }

    // GET /students/{student}/exercises/{exercise}
    public function show(Student $student, StudentExercise $exercise): JsonResponse
    {
        if ($exercise->student_id !== $student->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($exercise->load('exercise'));
    }

    // POST /students/{student}/exercises
    public function store(Request $request, Student $student): JsonResponse
    {
        $request->headers->set('Accept', 'application/json'); // double-force JSON
        $data = $request->validate([
            'exercise_id'       => [
                'required', 'exists:exercises,id',
                Rule::unique('student_exercises')
                    ->where(fn($q) => $q->where('student_id', $student->id)),
            ],
            'sets'              => 'nullable|integer|min:1|max:100',
            'repetitions'       => 'nullable|integer|min:1|max:1000',
            'weight'            => 'nullable|numeric|min:0|max:999.99',
            'duration_seconds'  => 'nullable|integer|min:1|max:86400',            
            'note'              => 'nullable|string',
        ]);

        $record = StudentExercise::create([
            'student_id'       => $student->id,
            'exercise_id'      => $data['exercise_id'],
            'sets'             => $data['sets'] ?? null,
            'repetitions'      => $data['repetitions'] ?? null,
            'weight'           => $data['weight'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? null,            
            'note'             => $data['note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Exercise assigned successfully.',
            'data'    => $record->load('exercise'),
        ], 201);
    }


    // PUT/PATCH /students/{student}/exercises/{exercise}
    public function update(Request $request, Student $student, StudentExercise $exercise): JsonResponse
    {
        $request->headers->set('Accept', 'application/json'); // double-force JSON
        if ($exercise->student_id !== $student->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'exercise_id'       => [
                'sometimes', 'required', 'exists:exercises,id',
                Rule::unique('student_exercises')
                    ->where(fn($q) => $q->where('student_id', $student->id))
                    ->ignore($exercise->id),
            ],
            'sets'              => 'nullable|integer|min:1|max:100',
            'repetitions'       => 'nullable|integer|min:1|max:1000',
            'weight'            => 'nullable|numeric|min:0|max:999.99',
            'duration_seconds'  => 'nullable|integer|min:1|max:86400',            
            'note'              => 'nullable|string',
        ]);

        $exercise->fill($data)->save();

        return response()->json([
            'message' => 'Exercise updated successfully.',
            'data'    => $exercise->fresh()->load('exercise'),
        ]);
    }


    // DELETE /students/{student}/exercises/{exercise}
    public function destroy(Request $request,Student $student, StudentExercise $student_exercise): JsonResponse
    {
        $request->headers->set('Accept', 'application/json'); // double-force JSON
        if ($student_exercise->student_id !== $student->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $student_exercise->delete();

        return response()->json(['message' => 'Exercise deleted successfully.']);
    }
}
