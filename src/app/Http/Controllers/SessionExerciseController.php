<?php
namespace App\Http\Controllers;

use App\Models\SessionExercise;
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SessionExerciseController extends Controller
{
    // GET /api/classes/{class}/sessions/{session}/exercises
    public function index($classId, $sessionId): JsonResponse
    {
        $this->ensureSessionBelongsToClass($classId, $sessionId);

        $rows = SessionExercise::with('exercise')
            ->where('session_id', $sessionId)
            ->orderBy('display_order')
            ->get();

        return response()->json($rows);
    }

    // GET /api/classes/{class}/sessions/{session}/exercises/{id}
    public function show($classId, $sessionId, $id): JsonResponse
    {
        $this->ensureSessionBelongsToClass($classId, $sessionId);

        $row = SessionExercise::with('exercise')
            ->where('session_id', $sessionId)
            ->findOrFail($id);

        return response()->json($row);
    }

    // POST /api/classes/{class}/sessions/{session}/exercises
    public function store($classId, $sessionId, Request $request): JsonResponse
    {
        $this->ensureSessionBelongsToClass($classId, $sessionId);

        $data = $request->validate([
            'exercise_id'   => ['required','integer','exists:exercises,id'],
            'display_order' => ['nullable','integer','min:1','max:65535'],
            'note'          => ['nullable','string','max:2000'],
        ]);

        $data['session_id'] = $sessionId;
        $row = SessionExercise::create($data);

        return response()->json($row->load('exercise'), 201);
    }

    // PATCH /api/classes/{class}/sessions/{session}/exercises/{id}
    public function update($classId, $sessionId, $id, Request $request): JsonResponse
    {
        $this->ensureSessionBelongsToClass($classId, $sessionId);

        $row = SessionExercise::where('session_id', $sessionId)->findOrFail($id);

        $data = $request->validate([
            'exercise_id'   => ['nullable','integer','exists:exercises,id'],
            'display_order' => ['nullable','integer','min:1','max:65535'],
            'note'          => ['nullable','string','max:2000'],
        ]);

        $row->update($data);

        return response()->json($row->load('exercise'));
    }

    // DELETE /api/classes/{class}/sessions/{session}/exercises/{id}
    public function destroy($classId, $sessionId, $id): JsonResponse
    {
        $this->ensureSessionBelongsToClass($classId, $sessionId);

        $row = SessionExercise::where('session_id', $sessionId)->findOrFail($id);
        $row->delete();

        return response()->json(['deleted' => true]);
    }

    // --- helper
    protected function ensureSessionBelongsToClass($classId, $sessionId): void
    {
        if (! ClassSession::where('id',$sessionId)->where('class_id',$classId)->exists()) {
            abort(404, 'Session not found for this class');
        }
    }
}
