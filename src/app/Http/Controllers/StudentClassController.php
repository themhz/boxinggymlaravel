<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use Illuminate\Http\Request;

class StudentClassController extends Controller
{
    public function index(Student $student)
    {
        // was: with(['lesson','teacher'])
        $classes = $student->classes()->with(['lesson','teachers'])->get();
        return response()->json($classes);
    }

    public function store(Request $request, Student $student)
    {
        $data = $request->validate([
            'class_id' => 'required|integer|exists:classes,id',
            'status'   => 'nullable|string|max:50',
            'note'     => 'nullable|string|max:255',
        ]);

        // prevent dup
        if ($student->classes()->where('class_id', $data['class_id'])->exists()) {
            return response()->json(['message' => 'Student already in this class'], 409);
        }

        $student->classes()->attach($data['class_id'], [
            'status' => $data['status'] ?? null,
            'note'   => $data['note'] ?? null,
        ]);

        // was: with('lesson','teacher')
        return response()->json([
            'message' => 'Student added to class',
            'classes' => $student->classes()->with(['lesson','teachers'])->get(),
        ], 201);
    }

    public function update(Request $request, Student $student, ClassModel $class)
    {
        $data = $request->validate([
            'status' => 'nullable|string|max:50',
            'note'   => 'nullable|string|max:255',
        ]);

        if (! $student->classes()->where('class_id', $class->id)->exists()) {
            return response()->json(['message' => 'Not enrolled in this class'], 404);
        }

        $student->classes()->updateExistingPivot($class->id, $data);

        return response()->json([
            'message' => 'Enrollment updated',
            'pivot'   => $student->classes()->where('class_id', $class->id)->first()->pivot,
        ]);
    }

    public function destroy(Student $student, ClassModel $class)
    {
        if (! $student->classes()->where('class_id', $class->id)->exists()) {
            return response()->json(['message' => 'Not enrolled in this class'], 404);
        }

        $student->classes()->detach($class->id);

        return response()->json(['message' => 'Student removed from class']);
    }
}
