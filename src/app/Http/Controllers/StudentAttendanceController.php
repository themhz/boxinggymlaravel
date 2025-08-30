<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentAttendanceController extends Controller
{
    // GET /api/students/{student}/attendance?class_id=&status=&from=&to=&per_page=
    public function index(Request $request, Student $student)
    {
        $request->headers->set('Accept', 'application/json');

        $query = Attendance::with(['session.class.lesson'])
            ->where('student_id', $student->id);

        if ($request->filled('class_id')) {
            $query->whereHas('session', fn($q) =>
                $q->where('class_id', $request->integer('class_id'))
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('from')) {
            $query->whereHas('session', fn($q) =>
                $q->whereDate('date', '>=', $request->date('from'))
            );
        }
        if ($request->filled('to')) {
            $query->whereHas('session', fn($q) =>
                $q->whereDate('date', '<=', $request->date('to'))
            );
        }

        $query->orderByDesc('created_at');

        if ($request->integer('per_page', 0) > 0) {
            $paginator = $query->paginate($request->integer('per_page', 20));
            return response()->json([
                'result' => 'success',
                'data'   => $paginator, // includes items + meta
            ]);
        }

        return response()->json([
            'result' => 'success',
            'data'   => $query->get(),
        ]);
    }

    // GET /api/students/{student}/attendance/{attendance}
    public function show(Student $student, Attendance $attendance)
    {
        $request = request();
        $request->headers->set('Accept', 'application/json');

        // ensure the record belongs to that student
        if ($attendance->student_id !== $student->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Not found',
            ], 404);
        }

        return response()->json([
            'result' => 'success',
            'data'   => $attendance->load(['session.class.lesson']),
        ]);
    }

    // POST /api/students/{student}/attendance
    public function store(Request $request, Student $student)
    {
        $request->headers->set('Accept', 'application/json');

        $validated = $request->validate([
            'session_id' => ['required','exists:class_sessions,id',
                // prevent duplicates: a student can have only one record per session
                Rule::unique('attendances')->where(fn($q) =>
                    $q->where('student_id', $student->id)
                )
            ],
            // consider enum: present|absent|late|excused
            'status'     => ['required','string','max:50'],
            'note'       => ['nullable','string'],
        ]);

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'session_id' => $validated['session_id'],
            'status'     => $validated['status'],
            'note'       => $validated['note'] ?? null,
        ]);

        return response()->json([
            'result'   => 'success',
            'message'  => 'Attendance created',
            'data'     => $attendance->load(['session.class.lesson']),
        ], 201);
    }

    // PUT/PATCH /api/students/{student}/attendance/{attendance}
    public function update(Request $request, Student $student, Attendance $attendance)
    {
        $request->headers->set('Accept', 'application/json');

        if ($attendance->student_id !== $student->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Not found',
            ], 404);
        }

        $data = $request->validate([
            'session_id' => [
                'required',
                'exists:class_sessions,id',
                Rule::unique('attendances')
                    ->where(fn($q) => $q->where('student_id', $student->id))
                    ->ignore($attendance->id)
            ],
            'status' => 'required|string|max:50',
            'note'   => 'nullable|string',
        ]);

        $attendance->update($data);

        return response()->json([
            'result'   => 'success',
            'message'  => 'Attendance updated successfully.',
            'data'     => $attendance->fresh()->load(['session.class.lesson']),
        ]);
    }

    // DELETE /api/students/{student}/attendance/{attendance}
    public function destroy(Student $student, Attendance $attendance)
    {
        $request = request();
        $request->headers->set('Accept', 'application/json');

        if ($attendance->student_id !== $student->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Not found',
            ], 404);
        }

        $attendance->delete();

        return response()->json([
            'result'  => 'success',
            'message' => 'Attendance deleted',
        ]);
    }
}
