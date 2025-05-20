<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ClassSession; // your model

class ClassController extends Controller
{
    // public function index()
    // {
    //     return response()->json(ClassModel::with('lesson')->get());
    // }

    public function index(): JsonResponse
    {
        // eager-load the lesson relation once
        $classes = ClassModel::with('lesson')->get();

        // 1) offerings: one entry per lesson
        $offerings = $classes
            ->map(fn($c) => [
                'id'          => $c->lesson->id,
                'title'       => $c->lesson->title,
                'description' => $c->lesson->description,
                'image'       => $c->lesson->image,
            ])
            ->unique('id')
            ->values();

        // 2) schedule: group by day
        $schedule = $classes
            ->groupBy('day')
            ->mapWithKeys(function($group, $day) {
                // for each day, map to an array of sessions
                return [
                    $day => $group
                        ->map(fn($c) => [
                            'class'     => $c->lesson->title,
                            'start_time'=> $c->start_time,
                            'end_time'  => $c->end_time,
                            'capacity'  => $c->capacity,
                        ])
                        ->values()
                ];
            });

        return response()->json([
            'offerings' => $offerings,
            'schedule'  => $schedule,
        ]);
    }

    public function show($id)
    {
        $class = ClassModel::with('lesson')->findOrFail($id);
        //return response()->json("ddd");
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
