<?php
// app/Http/Controllers/SessionExerciseController.php

namespace App\Http\Controllers;

use App\Models\SessionExercise;
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SessionExerciseController extends Controller
{
    // GET /api/sessions/{session}/exercises
    public function index(ClassSession $session): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        $rows = SessionExercise::with('exercise')
            ->where('session_id', $session->id)
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'result' => 'success',
            'data'   => $rows,
        ]);
    }

    // GET /api/sessions/{session}/exercises/{session_exercise}
    public function show(ClassSession $session, SessionExercise $session_exercise): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        if ($session_exercise->session_id !== $session->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Exercise not found for this session',
            ], 404);
        }

        return response()->json([
            'result' => 'success',
            'data'   => $session_exercise->load('exercise'),
        ]);
    }

    // POST /api/sessions/{session}/exercises
    public function store(ClassSession $session, Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'exercise_id'   => ['required','integer','exists:exercises,id'],
            'display_order' => ['nullable','integer','min:1','max:65535'],
            'note'          => ['nullable','string','max:2000'],
        ]);

        $row = SessionExercise::create([
            'session_id'    => $session->id,
            'exercise_id'   => $data['exercise_id'],
            'display_order' => $data['display_order'] ?? null,
            'note'          => $data['note'] ?? null,
        ])->load('exercise');

        return response()->json([
            'result'  => 'success',
            'message' => 'Session exercise created',
            'data'    => $row,
        ], 201);
    }

    // PUT/PATCH /api/sessions/{session}/exercises/{session_exercise}
    public function update(Request $request, ClassSession $session, SessionExercise $session_exercise): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        if ($session_exercise->session_id !== $session->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Exercise not found for this session',
            ], 404);
        }

        $data = $request->validate([
            'exercise_id'   => ['sometimes','integer','exists:exercises,id'],
            'display_order' => ['sometimes','nullable','integer','min:1','max:65535'],
            'note'          => ['sometimes','nullable','string','max:2000'],
        ]);

        $session_exercise->update($data);

        return response()->json([
            'result'  => 'success',
            'message' => 'Session exercise updated',
            'data'    => $session_exercise->fresh()->load('exercise'),
        ]);
    }

    // DELETE /api/sessions/{session}/exercises/{session_exercise}
    public function destroy(ClassSession $session, SessionExercise $session_exercise): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        if ($session_exercise->session_id !== $session->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Exercise not found for this session',
            ], 404);
        }

        $session_exercise->delete();

        return response()->json([
            'result'  => 'success',
            'message' => 'Session exercise deleted',
        ]);
    }
}
