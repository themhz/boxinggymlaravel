<?php
namespace App\Http\Controllers;

use App\Models\SessionExerciseStudent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class SessionExerciseStudentController extends Controller
{
    // GET /api/session-exercise-students?session_id=&student_id=
    public function index(Request $request): JsonResponse
    {
        $q = SessionExerciseStudent::query()
        ->with(['session','student','sessionExercise','studentExercise']);

        if ($request->filled('session_id')) $q->where('session_id', $request->integer('session_id'));
        if ($request->filled('student_id')) $q->where('student_id', $request->integer('student_id'));

        return response()->json($q->orderByDesc('id')->get());
    }

    // POST /api/session-exercise-students
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id'            => ['required','integer','exists:class_sessions,id'],
            'student_id'            => ['required','integer','exists:students,id'],
            'session_exercise_id'   => ['nullable','integer','exists:session_exercise,id'],
            'student_exercise_id'   => ['nullable','integer','exists:student_exercises,id'],

            'performed_sets'            => ['nullable','integer','min:0','max:65535'],
            'performed_repetitions'     => ['nullable','integer','min:0','max:65535'],
            'performed_weight'          => ['nullable','numeric','min:0'],
            'performed_duration_seconds'=> ['nullable','integer','min:0'],

            'status' => ['required', Rule::in(['completed','skipped','partial'])],
        ]);

        $row = SessionExerciseStudent::create($data);
        return response()->json($row->load(['session','student','sessionExercise','studentExercise']), 201);
    }

    // GET /api/session-exercise-students/{id}
    public function show(SessionExerciseStudent $sessionExerciseStudent): JsonResponse
    {
        return response()->json($sessionExerciseStudent->load(['session','student','sessionExercise','studentExercise']));
    }

    // PATCH /api/session-exercise-students/{id}
    public function update(Request $request, SessionExerciseStudent $sessionExerciseStudent): JsonResponse
    {
        $data = $request->validate([
            'session_exercise_id'   => ['nullable','integer','exists:session_exercise,id'],
            'student_exercise_id'   => ['nullable','integer','exists:student_exercises,id'],
            'performed_sets'            => ['nullable','integer','min:0','max:65535'],
            'performed_repetitions'     => ['nullable','integer','min:0','max:65535'],
            'performed_weight'          => ['nullable','numeric','min:0'],
            'performed_duration_seconds'=> ['nullable','integer','min:0'],
            'status' => [Rule::in(['completed','skipped','partial'])],
        ]);

        $sessionExerciseStudent->update($data);
        return response()->json($sessionExerciseStudent->load(['session','student','sessionExercise','studentExercise']));
    }

    // DELETE /api/session-exercise-students/{id}
    public function destroy(SessionExerciseStudent $sessionExerciseStudent): JsonResponse
    {
        $sessionExerciseStudent->delete();
        return response()->json(['deleted' => true]);
    }
}
