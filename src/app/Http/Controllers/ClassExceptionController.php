<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\ClassException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClassExceptionController extends Controller
{
    // GET /api/classes/{class}/exceptions
    public function index(ClassModel $class): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        $exceptions = ClassException::with('class.lesson')
            ->where('class_id', $class->id)
            ->orderByDesc('exception_date')
            ->get();

        return response()->json([
            'result' => 'success',
            'data'   => $exceptions,
        ]);
    }

    // GET /api/classes/{class}/exceptions/{exception}
    public function show(ClassModel $class, ClassException $exception): JsonResponse
    {
        request()->headers->set('Accept', 'application/json');

        if ($exception->class_id !== $class->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Exception not found for this class.',
            ], 404);
        }

        return response()->json([
            'result' => 'success',
            'data'   => $exception->load('class.lesson'),
        ]);
    }

    // POST /api/classes/{class}/exceptions
    public function store(Request $request, ClassModel $class): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            // class_id comes from the URL; do NOT accept it from body
            'exception_date'      => 'required|date',
            'is_cancelled'        => 'sometimes|boolean',
            'override_start_time' => 'nullable|date_format:H:i',
            'override_end_time'   => 'nullable|date_format:H:i|after:override_start_time',
            'reason'              => 'nullable|string|max:255',
        ]);

        $exception = ClassException::create([
            'class_id'            => $class->id,
            'exception_date'      => $data['exception_date'],
            'is_cancelled'        => (bool)($data['is_cancelled'] ?? false),
            'override_start_time' => $data['override_start_time'] ?? null,
            'override_end_time'   => $data['override_end_time'] ?? null,
            'reason'              => $data['reason'] ?? null,
        ]);

        return response()->json([
            'result'  => 'success',
            'message' => 'Class exception created.',
            'data'    => $exception->load('class.lesson'),
        ], 201);
    }

    // PUT/PATCH /api/classes/{class}/exceptions/{exception}
    public function update(Request $request, ClassModel $class, ClassException $exception): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        if ($exception->class_id !== $class->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Exception not found for this class.',
            ], 404);
        }

        $data = $request->validate([
            // class_id stays tied to route param; do not allow changing it
            'exception_date'      => 'sometimes|required|date',
            'is_cancelled'        => 'sometimes|boolean',
            'override_start_time' => 'sometimes|nullable|date_format:H:i',
            'override_end_time'   => 'sometimes|nullable|date_format:H:i|after:override_start_time',
            'reason'              => 'sometimes|nullable|string|max:255',
        ]);

        $exception->fill($data);
        if (array_key_exists('is_cancelled', $data)) {
            $exception->is_cancelled = (bool)$data['is_cancelled'];
        }
        $exception->save();

        return response()->json([
            'result'  => 'success',
            'message' => 'Class exception updated.',
            'data'    => $exception->fresh()->load('class.lesson'),
        ]);
    }

    // DELETE /api/classes/{class}/exceptions/{exception}
    public function destroy(Request $request, ClassModel $class, ClassException $exception): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        if ($exception->class_id !== $class->id) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Exception not found for this class.',
            ], 404);
        }

        $exception->delete();

        return response()->json([
            'result'  => 'success',
            'message' => 'Exception deleted.',
        ]);
    }
}
