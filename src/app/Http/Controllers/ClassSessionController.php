<?php
// app/Http/Controllers/ClassSessionController.php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClassSessionController extends Controller
{
    // GET /api/classes/{class}/sessions
    public function index(ClassModel $class): JsonResponse
    {
        $items = ClassSession::where('class_id', $class->id)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'result' => 'success',
            'data'   => $items,
        ]);
    }

    // GET /api/classes/{class}/sessions/{session}
    public function show(ClassModel $class, ClassSession $session): JsonResponse
    {
        if ($session->class_id !== $class->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Session not found for this class',
            ], 404);
        }

        return response()->json([
            'result' => 'success',
            'data'   => $session,
        ]);
    }

    // POST /api/classes/{class}/sessions
    public function store(Request $request, ClassModel $class): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        // Adjust rules to match your schema (add/remove fields as needed)
        $data = $request->validate([
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'status'      => 'nullable|string|max:50',
            'location'    => 'nullable|string|max:100',
            'notes'        => 'nullable|string',
        ]);

        $session = ClassSession::create([
            'class_id'    => $class->id,
            'date'        => $data['date'],
            'start_time'  => $data['start_time'],
            'end_time'    => $data['end_time'],
            'status'      => $data['status'] ?? null,
            'location'    => $data['location'] ?? null,
            'notes'        => $data['note'] ?? null,
        ]);

        return response()->json([
            'result'  => 'success',
            'message' => 'Session created',
            'data'    => $session,
        ], 201);
    }

    // PUT/PATCH /api/classes/{class}/sessions/{session}
    public function update(Request $request, ClassModel $class, ClassSession $session): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        if ($session->class_id !== $class->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Session not found for this class',
            ], 404);
        }

        // Make all fields sometimes/nullable as appropriate
        $data = $request->validate([
            'date'        => 'sometimes|required|date',
            'start_time'  => 'sometimes|required|date_format:H:i',
            'end_time'    => 'sometimes|required|date_format:H:i|after:start_time',
            'status'      => 'sometimes|nullable|string|max:50',
            'location'    => 'sometimes|nullable|string|max:100',
            'notes'        => 'sometimes|nullable|string',
        ]);

        $session->fill($data)->save();

        return response()->json([
            'result'  => 'success',
            'message' => 'Session updated',
            'data'    => $session->fresh(),
        ]);
    }

    // DELETE /api/classes/{class}/sessions/{session}
    public function destroy(Request $request, ClassModel $class, ClassSession $session): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        if ($session->class_id !== $class->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Session not found for this class',
            ], 404);
        }

        $session->delete();

        return response()->json([
            'result'  => 'success',
            'message' => 'Session deleted',
        ]);
    }
}
