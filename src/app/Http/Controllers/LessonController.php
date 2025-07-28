<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:255',
            'teacher_ids' => 'required|array',
            'teacher_ids.*' => 'exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $lesson = Lesson::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'] ?? null,
            'image' => $validated['image'] ?? null,
        ]);

        $lesson->teachers()->attach($validated['teacher_ids']);

        return response()->json([
            'message' => 'Lesson created successfully',
            'lesson' => $lesson->load('teachers')
        ], 201);
    }

   public function update(Request $request, $id)
    {
        $lesson = Lesson::find($id);

        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'level' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:255',
            'teacher_ids' => 'sometimes|array',
            'teacher_ids.*' => 'exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Update lesson fields
        $lesson->update([
            'title' => $validated['title'] ?? $lesson->title,
            'description' => $validated['description'] ?? $lesson->description,
            'level' => $validated['level'] ?? $lesson->level,
            'image' => $validated['image'] ?? $lesson->image,
        ]);

        // Sync teacher relationships if provided
        if (isset($validated['teacher_ids'])) {
            $lesson->teachers()->sync($validated['teacher_ids']);
        }

        return response()->json([
            'message' => 'Lesson updated successfully',
            'lesson' => $lesson->load('teachers')
        ]);
    }

    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();

        return response()->noContent();
    }
}
