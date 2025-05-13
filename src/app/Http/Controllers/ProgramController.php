<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        return response()->json(Program::with('classType')->get());
    }

    public function show($id)
    {
        $program = Program::with('classType')->findOrFail($id);
        return response()->json($program);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_type_id' => 'required|exists:class_types,id',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'day' => 'required|string',
            'capacity' => 'required|integer|min:1',
        ]);

        $program = Program::create($validated);

        return response()->json([
            'message' => 'Program created successfully',
            'program' => $program
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        $validated = $request->validate([
            'class_type_id' => 'sometimes|exists:class_types,id',
            'start_time' => 'sometimes|date_format:H:i:s',
            'end_time' => 'sometimes|date_format:H:i:s|after:start_time',
            'day' => 'sometimes|string',
            'capacity' => 'sometimes|integer|min:1',
        ]);

        $program->update($validated);

        return response()->json([
            'message' => 'Program updated successfully',
            'program' => $program
        ]);
    }

    public function destroy($id)
    {
        $program = Program::findOrFail($id);
        $program->delete();

        return response()->json(['message' => 'Program deleted']);
    }

    public function available()
    {
        return response()->json(
            Program::with('classType')
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
