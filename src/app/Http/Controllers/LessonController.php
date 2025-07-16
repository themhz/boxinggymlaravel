<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index()
    {
        return response()->json(
            Lesson::select(['id', 'title', 'description', 'level', 'image'])->get()
        );
    }

    
    public function show($id)
    {
        $lesson = Lesson::findOrFail($id);
        return response()->json($lesson);
    }

    public function withTeachers()
    {
        $lessons = Lesson::with('teachers')->get()->map(function ($lesson) {
            return [
                'id'          => $lesson->id,
                'title'       => $lesson->title,
                'description' => $lesson->description,
                'level'       => $lesson->level,
                'image'       => $lesson->image,
                'teachers'    => $lesson->teachers->unique('id')->map(fn($t) => [
                    'id'    => $t->id,
                    'name'  => $t->name,
                    'email' => $t->email,
                ])->values()
            ];
        });

        return response()->json($lessons);
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'teacher_id' => 'required|exists:teachers,id',
        ]);

        $lesson = Lesson::create($validated);

        return response()->json([
            'message' => 'Lesson created successfully',
            'lesson' => $lesson
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'teacher_id' => 'sometimes|exists:teachers,id',
        ]);

        $lesson->update($validated);

        return response()->json([
            'message' => 'Lesson updated successfully',
            'lesson' => $lesson
        ]);
    }

    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();

        return response()->noContent();
    }
}
