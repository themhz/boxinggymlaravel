<?php

namespace App\Http\Controllers;

use App\Models\SessionExerciseStudent;
use App\Models\Student;
use App\Models\StudentExercise;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class SessionExerciseStudentController extends Controller
{
    // GET /api/session/{session}/exercise/{student_exercise}/students
    public function index(Request $request, int $session, StudentExercise $student_exercise): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $q = SessionExerciseStudent::with(['session','student','sessionExercise','studentExercise'])
            ->where('session_id', $session)
            ->where('student_exercise_id', $student_exercise->id);

        // Admin sees all; students see only their own
        if (! $request->user()?->can('manage-session-exercise-students')) {
            $authStudent = Student::where('user_id', $request->user()->id ?? null)->first();
            if (! $authStudent) {
                return response()->json(['result' => 'error', 'message' => 'Forbidden'], 403);
            }
            $q->where('student_id', $authStudent->id);
        }

        return response()->json([
            'result' => 'success',
            'data'   => $q->orderByDesc('id')->get(),
        ]);
    }

    // GET /api/session/{session}/exercise/{student_exercise}/students/{student}
    public function show(Request $request, int $session, StudentExercise $student_exercise, Student $student): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        if (! $request->user()?->can('manage-session-exercise-students')) {
            $authStudent = Student::where('user_id', $request->user()->id ?? null)->first();
            if (! $authStudent || $authStudent->id !== $student->id) {
                return response()->json(['result' => 'error', 'message' => 'Forbidden'], 403);
            }
        }

        $row = SessionExerciseStudent::with(['session','student','sessionExercise','studentExercise'])
            ->where('session_id', $session)
            ->where('student_exercise_id', $student_exercise->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $row) {
            return response()->json(['result' => 'error', 'message' => 'Not found'], 404);
        }

        return response()->json([
            'result' => 'success',
            'data'   => $row,
        ]);
    }

    // POST /api/session/{session}/exercise/{student_exercise}/students
    public function store(Request $request, int $session, StudentExercise $student_exercise): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        // Admin-only create
        if (! $request->user()?->can('manage-session-exercise-students')) {
            return response()->json(['result' => 'error', 'message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'student_id'                 => ['required','integer','exists:students,id'],
            'session_exercise_id'        => ['nullable','integer','exists:session_exercise,id'], // optional
            'performed_sets'             => ['nullable','integer','min:0','max:65535'],
            'performed_repetitions'      => ['nullable','integer','min:0','max:65535'],
            'performed_weight'           => ['nullable','numeric','min:0'],
            'performed_duration_seconds' => ['nullable','integer','min:0'],
            'status'                     => ['required', Rule::in(['completed','skipped','partial'])],
        ]);

        $row = SessionExerciseStudent::create([
            'session_id'                 => $session,
            'student_id'                 => $data['student_id'],
            'student_exercise_id'        => $student_exercise->id,
            'session_exercise_id'        => $data['session_exercise_id'] ?? null, // keep nullable if you don’t want to couple to session_exercises
            'performed_sets'             => $data['performed_sets'] ?? null,
            'performed_repetitions'      => $data['performed_repetitions'] ?? null,
            'performed_weight'           => $data['performed_weight'] ?? null,
            'performed_duration_seconds' => $data['performed_duration_seconds'] ?? null,
            'status'                     => $data['status'],
        ])->load(['session','student','sessionExercise','studentExercise']);

        return response()->json([
            'result'  => 'success',
            'message' => 'Session exercise student created',
            'data'    => $row,
        ], 201);
    }

    // PUT/PATCH /api/session/{session}/exercise/{student_exercise}/students/{student}
    public function update(Request $request, int $session, StudentExercise $student_exercise, Student $student): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        if (! $request->user()?->can('manage-session-exercise-students')) {
            return response()->json(['result' => 'error', 'message' => 'Forbidden'], 403);
        }

        $row = SessionExerciseStudent::where('session_id', $session)
            ->where('student_exercise_id', $student_exercise->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $row) {
            return response()->json(['result' => 'error', 'message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'session_exercise_id'        => ['sometimes','nullable','integer','exists:session_exercise,id'],
            'performed_sets'             => ['sometimes','nullable','integer','min:0','max:65535'],
            'performed_repetitions'      => ['sometimes','nullable','integer','min:0','max:65535'],
            'performed_weight'           => ['sometimes','nullable','numeric','min:0'],
            'performed_duration_seconds' => ['sometimes','nullable','integer','min:0'],
            'status'                     => ['sometimes', Rule::in(['completed','skipped','partial'])],
        ]);

        $row->update($data);

        return response()->json([
            'result'  => 'success',
            'message' => 'Session exercise student updated',
            'data'    => $row->fresh()->load(['session','student','sessionExercise','studentExercise']),
        ]);
    }

    // DELETE /api/session/{session}/exercise/{student_exercise}/students/{student}
    public function destroy(Request $request, int $session, StudentExercise $student_exercise, Student $student): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        if (! $request->user()?->can('manage-session-exercise-students')) {
            return response()->json(['result' => 'error', 'message' => 'Forbidden'], 403);
        }

        $row = SessionExerciseStudent::where('session_id', $session)
            ->where('student_exercise_id', $student_exercise->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $row) {
            return response()->json(['result' => 'error', 'message' => 'Not found'], 404);
        }

        $row->delete();

        return response()->json([
            'result'  => 'success',
            'message' => 'Session exercise student deleted',
        ]);
    }

    public function indexForSession(Request $request, int $session): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $q = SessionExerciseStudent::with([
            'student:id,name,email',
            'sessionExercise.exercise',   // session_exercises → exercise details
            'studentExercise.exercise',   // student_exercises → exercise details (optional)
        ])->where('session_id', $session);

        // Admin sees all; students see only their own
        if (! $request->user()?->can('manage-session-exercise-students')) {
            $authStudent = \App\Models\Student::where('user_id', $request->user()->id ?? null)->first();
            if (! $authStudent) {
                return response()->json(['result' => 'error', 'message' => 'Forbidden'], 403);
            }
            $q->where('student_id', $authStudent->id);
        }

        $rows = $q->orderBy('session_exercise_id')->orderByDesc('id')->get();

        return response()->json([
            'result' => 'success',
            'data'   => $rows,
        ]);
    }

}
