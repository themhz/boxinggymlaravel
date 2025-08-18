<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClassSessionAttendanceController extends Controller
{
    // GET /api/classes/{class}/sessions/{session}/attendances
    public function index($classId, $sessionId): JsonResponse
    {
        $this->ensureSessionBelongsToClass($classId, $sessionId);

        $rows = Attendance::with('student')
            ->where('session_id', $sessionId)
            ->get();

        return response()->json($rows);
    }

    // GET /api/classes/{class}/sessions/{session}/attendances/{id}
    public function show($classId, $sessionId, $id): JsonResponse
    {
        $this->ensureSessionBelongsToClass($classId, $sessionId);

        $row = Attendance::with('student')
            ->where('session_id', $sessionId)
            ->findOrFail($id);

        return response()->json($row);
    }

    // POST /api/classes/{class}/sessions/{session}/attendances
    public function store($classId, $sessionId, Request $request): JsonResponse
    {
        $this->ensureSessionBelongsToClass($classId, $sessionId);

        $data = $request->validate([
            'student_id' => ['required','integer','exists:students,id'],
            'status'     => ['nullable','in:present,absent,late'],
            'note'       => ['nullable','string','max:2000'],
        ]);

        $data['session_id'] = $sessionId;
        $row = Attendance::create($data);

        return response()->json($row->load('student'), 201);
    }

    // PATCH /api/classes/{class}/sessions/{session}/attendances/{id}
    public function update($classId, $sessionId, $id, Request $request): JsonResponse
    {
        $this->ensureSessionBelongsToClass($classId, $sessionId);

        $row = Attendance::where('session_id', $sessionId)->findOrFail($id);

        $data = $request->validate([
            'status' => ['nullable','in:present,absent,late'],
            'note'   => ['nullable','string','max:2000'],
        ]);

        $row->update($data);

        return response()->json($row->load('student'));
    }

    // DELETE /api/classes/{class}/sessions/{session}/attendances/{id}
    public function destroy($classId, $sessionId, $id): JsonResponse
    {
        $this->ensureSessionBelongsToClass($classId, $sessionId);

        $row = Attendance::where('session_id', $sessionId)->findOrFail($id);
        $row->delete();

        return response()->json(['deleted' => true]);
    }

    protected function ensureSessionBelongsToClass($classId, $sessionId): void
    {
        if (! ClassSession::where('id',$sessionId)->where('class_id',$classId)->exists()) {
            abort(404, 'Session not found for this class');
        }
    }
}
