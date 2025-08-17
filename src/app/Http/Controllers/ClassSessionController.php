<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ClassSessionController extends Controller
{
    use AuthorizesRequests;

    public function index(ClassModel $class)
    {
        return $class->sessions()->with('exercises')->get();
    }

    public function show(ClassModel $class, $id)
    {
        return $class->sessions()->with('exercises')->findOrFail($id);
    }

    public function store(Request $request, $classId)
    {
        $data = $request->validate([
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time'   => 'required|date_format:H:i:s|after:start_time',
            'notes'      => 'nullable|string|max:255',
        ]);

        $session = ClassSession::create([
            'class_id'   => $classId,
            ...$data
        ]);

        return response()->json($session, 201);
    }


    public function update(Request $request, ClassModel $class, $id)
    {
        $this->authorize('manage-class-sessions');

        $session = $class->sessions()->findOrFail($id);

        $data = $request->validate([
            'session_date' => 'sometimes|date',
            'notes'        => 'nullable|string|max:500',
        ]);

        $session->update($data);

        return response()->json($session);
    }

    public function destroy(ClassModel $class, $id)
    {
        $this->authorize('manage-class-sessions');

        $session = $class->sessions()->findOrFail($id);
        $session->delete();

        return response()->json(null, 204);
    }
}
