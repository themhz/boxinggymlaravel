<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index()
    {
        return Teacher::with('user')->get(); // ✅ no 'team' relationship
    }

    public function show($id)
    {
        $teacher = Teacher::with('user')->findOrFail($id);
        return response()->json($teacher);
    }

    public function lessons($id)
    {
        $teacher = Teacher::with('lessons')->findOrFail($id);

        $lessons = $teacher->lessons->map(fn($lesson) => [
            'id'          => $lesson->id,
            'title'       => $lesson->title,
            'description' => $lesson->description,
            'level'       => $lesson->level,
            'image'       => $lesson->image,
        ]);

        return response()->json($lessons);
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|string|max:255',
            'team_id' => 'required|exists:teams,id',
            'user_id' => 'required|exists:users,id', // ✅ Add this line
        ]);

        $teacher = Teacher::create($validated); // ✅ Will now include user_id

        return response()->json([
            'message' => 'Teacher created successfully',
            'teacher' => $teacher
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'specialty' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string',
            'photo' => 'sometimes|string',
            'team_id' => 'sometimes|exists:teams,id',
        ]);

        $teacher->update($validated);

        return response()->json([
            'message' => 'Teacher updated successfully',
            'teacher' => $teacher
        ]);
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();

        return response()->json(['message' => 'Teacher deleted']);
    }
}
