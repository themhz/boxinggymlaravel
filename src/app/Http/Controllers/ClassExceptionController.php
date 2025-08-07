<?php

namespace App\Http\Controllers;

use App\Models\ClassException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
class ClassExceptionController extends Controller
{
    public function index(): JsonResponse
    {
        $exceptions = ClassException::with('class.lesson')
            ->orderBy('exception_date', 'desc')
            ->get();

        return response()->json($exceptions);
    }

    public function show($id): JsonResponse
    {
        $exception = ClassException::with('class.lesson')->find($id);

        if (!$exception) {
            return response()->json(['message' => 'Exception not found.'], 404);
        }

        return response()->json($exception);
    }

    public function create()
    {
        $classes = \App\Models\ClassModel::all();
        return view('exceptions.create', compact('classes'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'exception_date' => 'required|date',
            'is_cancelled' => 'sometimes|boolean',
            'override_start_time' => 'nullable|date_format:H:i',
            'override_end_time' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:255',
        ]);

        $data['is_cancelled'] = $data['is_cancelled'] ?? false;


        $exception = ClassException::create($data);

        return response()->json([
            'message' => 'Class exception created.',
            'exception' => $exception,
        ], 201);
    }

    public function edit(ClassException $exception)
    {
        $classes = \App\Models\ClassModel::all();
        return view('exceptions.edit', compact('exception','classes'));
    }

    public function update(Request $request, ClassException $classes_exception)
    {
        $data = $request->validate([
            'class_id'            => 'sometimes|required|exists:classes,id',
            'exception_date'      => 'sometimes|required|date',
            'is_cancelled'        => 'sometimes|required|boolean',
            'override_start_time' => 'sometimes|required|date_format:H:i:s',
            'override_end_time'   => 'sometimes|required|date_format:H:i:s',
        ]);

        // Force-set the field to make sure it's picked up
        $classes_exception->fill($data);
        $classes_exception->is_cancelled = (bool) $data['is_cancelled'];

        $classes_exception->save(); // this saves the EXISTING record — not create a new one

        return response()->json([
            'message' => 'Class exception updated.',
            'data' => $classes_exception->fresh(),
        ]);
    }




    // Delete exception
    public function destroy($id): JsonResponse
    {
        $exception = ClassException::find($id);

        if (!$exception) {
            return response()->json(['message' => 'Exception not found.'], 404);
        }

        $exception->delete();

        return response()->json(['message' => 'Exception deleted.']);
    }

    
}
