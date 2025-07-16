<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('user')->get();

        return response()->json($students);
    }

    public function show($id)
    {
        $student = Student::with('user')->findOrFail($id);
        return response()->json($student);
    }

    public function studentLessons($id)
    {
        $student = Student::with('classes.lesson')->findOrFail($id);

        $uniqueLessons = $student->classes
            ->map(fn($class) => $class->lesson)
            ->unique('id')
            ->values()
            ->map(fn($lesson) => [
                'id'          => $lesson->id,
                'title'       => $lesson->title,
                'description' => $lesson->description,
                'level'       => $lesson->level,
                'image'       => $lesson->image,
            ]);

        return response()->json($uniqueLessons);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email',
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            // 'team_id' => 'nullable|exists:teams,id',
        ]);

        // Attach the authenticated user's ID
        $validated['user_id'] = $request->user()->id;

        $student = Student::create($validated);

        return response()->json([
            'message' => 'Student created successfully',
            'student' => $student
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:students,email,' . $student->id,
            'phone' => 'sometimes|string|max:20',
            'dob' => 'sometimes|date',            
        ]);

        $student->update($validated);

        return response()->json([
            'message' => 'Student updated successfully',
            'student' => $student
        ]);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return response()->noContent(); // <- This returns 204
    }

}
