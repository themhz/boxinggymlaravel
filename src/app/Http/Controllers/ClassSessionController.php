<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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


}