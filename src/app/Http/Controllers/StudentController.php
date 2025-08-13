<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

    public function studentClasses($id)
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
        $request->headers->set('Accept', 'application/json'); // double-force JSON
        // Only admins can create students
        Gate::authorize('students.create');

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:students,email',
            'phone'   => 'nullable|string|max:20',
            'dob'     => 'nullable|date',
            'image'   => 'nullable|string',
            // must exist AND be unused by any other student
            'user_id' => 'required|integer|exists:users,id|unique:students,user_id',
        ], [
            'user_id.unique' => 'This user already has a student profile.',
        ]);

        $student = Student::create($validated)->refresh();

        return response()->json(['message' => 'Student created successfully','student' => $student], 201);
    }




    public function update(Request $request, Student $student)
    {
        $request->headers->set('Accept', 'application/json'); // double-force JSON
        // Only admins can update students
        Gate::authorize('students.create'); // same gate we used for create

        $validated = $request->validate([
            'name'    => 'sometimes|required|string|max:255',
            'email'   => 'sometimes|required|email|unique:students,email,' . $student->id,
            'phone'   => 'nullable|string|max:20',
            'dob'     => 'nullable|date',
            'image'   => 'nullable|string',
            // must exist and be unique in students table except current
            'user_id' => 'sometimes|required|integer|exists:users,id|unique:students,user_id,' . $student->id,
        ], [
            'user_id.unique' => 'This user already has a student profile.',
        ]);

        $student->update($validated);

        return response()->json([
            'message' => 'Student updated successfully',
            'student' => $student->fresh()
        ]);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return response()->noContent(); // <- This returns 204
    }

}
