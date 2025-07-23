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
            'is_cancelled' => 'boolean',
            'override_start_time' => 'nullable|date_format:H:i',
            'override_end_time' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:255',
        ]);

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

    public function update(Request $request, ClassException $exception)
    {
        $data = $request->validate([
            'class_id'            => 'required|exists:classes,id',
            'exception_date'      => 'required|date',
            'is_cancelled'        => 'boolean',
            'override_start_time' => 'nullable|date_format:H:i:s',
            'override_end_time'   => 'nullable|date_format:H:i:s',
        ]);

        $exception->update($data);

        return redirect()->route('exceptions.index')
                         ->with('success', 'Exception updated.');
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
