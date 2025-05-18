<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index()
    {
        return response()->json(ClassModel::with('lesson')->get());
    }

    public function show($id)
    {
        $class = ClassModel::with('lesson')->findOrFail($id);
        return response()->json($class);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'day' => 'required|string',
            'capacity' => 'required|integer|min:1',
        ]);

        $class = ClassModel::create($validated);

        return response()->json([
            'message' => 'Class created successfully',
            'class' => $class
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $class = ClassModel::findOrFail($id);

        $validated = $request->validate([
            'lesson_id' => 'sometimes|exists:lessons,id',
            'start_time' => 'sometimes|date_format:H:i:s',
            'end_time' => 'sometimes|date_format:H:i:s|after:start_time',
            'day' => 'sometimes|string',
            'capacity' => 'sometimes|integer|min:1',
        ]);

        $class->update($validated);

        return response()->json([
            'message' => 'Class updated successfully',
            'class' => $class
        ]);
    }

    public function destroy($id)
    {
        $class = ClassModel::findOrFail($id);
        $class->delete();

        return response()->noContent(); // 204 response
    }

    public function available()
    {
        return response()->json(
            ClassModel::with('lesson')
                ->withCount([
                    'appointments as booked_count' => function ($q) {
                        $q->where('status', 'booked');
                    }
                ])
                ->havingRaw('booked_count < capacity')
                ->get()
        );
    }
}
