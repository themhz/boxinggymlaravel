<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Student;
use Illuminate\Http\Request;

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
}