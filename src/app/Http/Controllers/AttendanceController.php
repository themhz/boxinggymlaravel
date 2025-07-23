<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    public function index()
    {
        $records = Attendance::with(['session','student'])->paginate(20);
        return view('attendances.index', compact('records'));
    }

    public function create()
    {
        $sessions = ClassSession::all();
        $students = Student::all();
        return view('attendances.create', compact('sessions','students'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'session_id' => 'required|exists:class_sessions,id',
            'student_id' => 'required|exists:students,id',
            'status'     => 'required|in:present,absent',
        ]);

        Attendance::create($data);

        return redirect()->route('attendances.index')
                         ->with('success', 'Attendance recorded.');
    }

    public function edit(Attendance $attendance)
    {
        $sessions = ClassSession::all();
        $students = Student::all();
        return view('attendances.edit', compact('attendance','sessions','students'));
    }

    public function update(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'status'     => 'required|in:present,absent',
        ]);

        $attendance->update($data);

        return redirect()->route('attendances.index')
                         ->with('success', 'Attendance updated.');
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return redirect()->route('attendances.index')
                         ->with('success', 'Record deleted.');
    }

    public function apiBySession($sessionId): JsonResponse
    {
        $attendances = Attendance::with('student')
            ->where('session_id', $sessionId)
            ->get()
            ->map(function ($a) {
                return [
                    'student_id' => $a->student_id,
                    'student_name' => $a->student->name,
                    'status' => $a->status,
                    'recorded_at' => $a->created_at,
                ];
            });

        return response()->json([
            'session_id' => $sessionId,
            'attendances' => $attendances,
        ]);
    }

    public function apiIndex(): JsonResponse
    {
        $attendances = Attendance::with('student', 'session.relatedClass.lesson')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'status' => $a->status,
                    'recorded_at' => $a->created_at,
                    'student' => [
                        'id' => $a->student->id,
                        'name' => $a->student->name,
                    ],
                    'session' => [
                        'id' => $a->session->id,
                        'date' => $a->session->session_date,
                        'class_day' => optional($a->session->relatedClass)->day,
                        'lesson' => optional($a->session->relatedClass->lesson)->title,
                    ],
                ];
            });

        return response()->json($attendances);
    }

    public function apiByIdAndSession($attend, $sessionId): JsonResponse
    {
        $attendance = Attendance::with(['student', 'session.relatedClass.lesson'])
            ->where('id', $attend)
            ->where('session_id', $sessionId)
            ->first();

        if (!$attendance) {
            return response()->json([
                'message' => 'Attendance record not found.'
            ], 404);
        }

        return response()->json([
            'id' => $attendance->id,
            'status' => $attendance->status,
            'recorded_at' => $attendance->created_at,
            'student' => [
                'id' => $attendance->student->id,
                'name' => $attendance->student->name,
            ],
            'session' => [
                'id' => $attendance->session->id,
                'date' => $attendance->session->session_date,
                'class_day' => optional($attendance->session->relatedClass)->day,
                'lesson' => optional($attendance->session->relatedClass->lesson)->title,
            ],
        ]);
    }

    public function apiShow($id): JsonResponse
    {
        $attendance = Attendance::with(['student', 'session.relatedClass.lesson'])
            ->find($id);

        if (!$attendance) {
            return response()->json(['message' => 'Attendance not found.'], 404);
        }

        return response()->json([
            'id' => $attendance->id,
            'status' => $attendance->status,
            'student' => [
                'id' => $attendance->student->id,
                'name' => $attendance->student->name,
            ],
            'session' => [
                'id' => $attendance->session->id,
                'date' => $attendance->session->session_date,
                'lesson' => optional($attendance->session->relatedClass->lesson)->title,
            ]
        ]);
    }


}