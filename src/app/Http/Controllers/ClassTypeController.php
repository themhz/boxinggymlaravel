<?php

namespace App\Http\Controllers;

use App\Models\ClassType;
use Illuminate\Http\Request;

class ClassTypeController extends Controller
{
    public function index()
    {
        return response()->json(ClassType::with('teacher')->get());
    }

    public function show($id)
    {
        $classType = ClassType::with('teacher')->findOrFail($id);
        return response()->json($classType);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'nullable|string',
            'image' => 'nullable|string',
            'teacher_id' => 'required|exists:teachers,id',
        ]);

        $classType = ClassType::create($validated);

        return response()->json([
            'message' => 'Class type created successfully',
            'class_type' => $classType
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $classType = ClassType::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'level' => 'sometimes|string',
            'image' => 'sometimes|string',
            'teacher_id' => 'sometimes|exists:teachers,id',
        ]);

        $classType->update($validated);

        return response()->json([
            'message' => 'Class type updated successfully',
            'class_type' => $classType
        ]);
    }

    public function destroy($id)
    {
        $classType = ClassType::findOrFail($id);
        $classType->delete();

        return response()->json(['message' => 'Class type deleted']);
    }
}
