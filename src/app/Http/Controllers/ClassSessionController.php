<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\ClassModel;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClassSessionController extends Controller
{
    public function index()
    {
        $sessions = ClassSession::with('classModel.lesson')->paginate(20);
        return view('sessions.index', compact('sessions'));
    }

    public function create()
    {
        $classes = ClassModel::all();
        return view('sessions.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id'     => 'required|exists:classes,id',
            'session_date' => 'required|date',
        ]);

        ClassSession::create($data);

        return redirect()->route('sessions.index')
                         ->with('success', 'Session created.');
    }

    public function edit(ClassSession $session)
    {
        $classes = ClassModel::all();
        return view('sessions.edit', compact('session','classes'));
    }

    public function update(Request $request, ClassSession $session)
    {
        $data = $request->validate([
            'class_id'     => 'required|exists:classes,id',
            'session_date' => 'required|date',
        ]);

        $session->update($data);

        return redirect()->route('sessions.index')
                         ->with('success', 'Session updated.');
    }

    public function destroy(ClassSession $session)
    {
        $session->delete();
        return redirect()->route('sessions.index')
                         ->with('success', 'Session deleted.');
    }

    public function apiIndex(): JsonResponse
    {
        $sessions = ClassSession::with([
            'attendances.student',
            'exercises.exercise',
            'relatedClass.lesson',  // ✅ correct relationship name
        ])->get();

        return response()->json($sessions);
    }
    
    // GET /api/classes-sessions
    public function apiClassesWithSessions(): JsonResponse
    {
        $classes = ClassModel::with([
            'sessions.relatedClass.lesson',
            'sessions.attendances.student',
            'sessions.exercises.exercise',
        ])->get();

        return response()->json($classes);
    }

    // GET /api/classes-sessions/{id}
    public function apiClassSessionsById(int $id): JsonResponse
    {
        $class = ClassModel::with([
            'sessions.relatedClass.lesson',
            'sessions.attendances.student',
            'sessions.exercises.exercise',
        ])->findOrFail($id);

        return response()->json($class);
    }

    public function apiStudentAttendance($id): JsonResponse
    {
        $student = Student::with([
            'attendances.session.relatedClass.lesson'
        ])->findOrFail($id);

        // Optional: transform for cleaner output
        $data = [
            'student' => $student->name,
            'attendances' => $student->attendances->map(function ($a) {
                return [
                    'session_date' => $a->session->session_date,
                    'status' => $a->status,
                    'lesson' => optional($a->session->relatedClass->lesson)->title,
                    'day' => optional($a->session->relatedClass)->day,
                    'start_time' => optional($a->session->relatedClass)->start_time,
                ];
            }),
        ];

        return response()->json($data);
    }

    public function apiStudentExercises($id): JsonResponse
    {
        $student = Student::with('exercises')->findOrFail($id);

        $exercises = $student->exercises ?? collect(); // fallback to empty collection

        $data = [
            'student' => $student->name,
            'exercises' => $exercises->map(function ($e) {
                return [
                    'name' => $e->name,
                    'description' => $e->description,
                    'sets' => $e->sets,
                    'reps' => $e->repetitions,
                ];
            }),
        ];

        return response()->json($data);
    }

    public function apiSessionExercises($id): JsonResponse
    {
        $session = ClassSession::with('exercises.exercise')->findOrFail($id);

        $exercises = $session->exercises->map(function ($e) {
            return [
                'name' => $e->exercise->name,
                'description' => $e->exercise->description,
                'sets' => $e->exercise->sets,
                'reps' => $e->exercise->repetitions,
            ];
        });

        return response()->json([
            'session_id' => $session->id,
            'session_date' => $session->session_date,
            'exercises' => $exercises,
        ]);
    
    }

    // GET /api/sessions/{id}
    public function apiShow($id): JsonResponse
    {
        $session = ClassSession::with([
            'attendances.student',
            'exercises.exercise',
            'relatedClass.lesson'
        ])->findOrFail($id);

        return response()->json($session);
    }



    public function apiStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'class_id'     => 'required|exists:classes,id',
            'session_date' => 'required|date',
        ]);

        $session = ClassSession::create($data);

        return response()->json([
            'message' => 'Class session created successfully',
            'session' => $session
        ], 201);
    }

    public function apiUpdate(Request $request, $id): JsonResponse
    {
        try {
            $session = ClassSession::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $data = $request->validate([
            'class_id'     => 'sometimes|exists:classes,id',
            'session_date' => 'sometimes|date',
        ]);

        $session->update($data);

        return response()->json([
            'message' => 'Class session updated successfully',
            'session' => $session
        ]);
    }

    public function apiDestroy($id): JsonResponse
    {
        $session = ClassSession::find($id);

        if (!$session) {
            return response()->json(['deleted' => 0], 404);
        }

        $deleted = $session->delete();

        return response()->json(['deleted' => $deleted ? 1 : 0]);
    }




}