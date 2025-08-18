<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherController extends Controller
{
    // GET /api/teachers
    public function index(): JsonResponse
    {
        // Eager-load relations if you have any, e.g. classes, lessons
        $teachers = Teacher::query()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($teachers);
    }

    // GET /api/teachers/{teacher}
    public function show(Teacher $teacher): JsonResponse
    {
        return response()->json($teacher);
    }

    // POST /api/teachers
    public function store(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json'); // double-force JSON
        $data = $request->validate([
            'user_id'   => 'required|exists:users,id',
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:teachers,email',
            'phone'      => 'nullable|string|max:50',
            'bio'        => 'nullable|string',
            'hire_date'  => 'nullable|date',
            'is_active'  => 'sometimes|boolean',
        ]);

        // default is_active to true if omitted
        if (!array_key_exists('is_active', $data)) {
            $data['is_active'] = true;
        }

        $teacher = Teacher::create($data);

        return response()->json([
            'message' => 'Teacher created.',
            'data' => $teacher,
        ], 201);
    }

    // PUT/PATCH /api/teachers/{teacher}
    public function update(Request $request, Teacher $teacher): JsonResponse
    {
        $request->headers->set('Accept', 'application/json'); // double-force JSON
        $data = $request->validate([            
            'first_name' => 'sometimes|required|string|max:100',
            'last_name'  => 'sometimes|required|string|max:100',
            'email'      => 'sometimes|required|email|unique:teachers,email,' . $teacher->id,
            'phone'      => 'nullable|string|max:50',
            'bio'        => 'nullable|string',
            'hire_date'  => 'nullable|date',
            'is_active'  => 'sometimes|boolean',
        ]);

        $teacher->fill($data)->save();

        return response()->json([
            'message' => 'Teacher updated.',
            'data' => $teacher->fresh(),
        ]);
    }

    // DELETE /api/teachers/{teacher}
    public function destroy(Request $request,Teacher $teacher): JsonResponse
    {
        $request->headers->set('Accept', 'application/json'); // double-force JSON
        $teacher->delete();

        return response()->json(['message' => 'Teacher deleted.']);
    }

    public function lessons(Teacher $teacher): JsonResponse
    {
        $lessons = Lesson::whereHas('classes.teachers', function ($q) use ($teacher) {
                $q->where('teachers.id', $teacher->id);
            })
            ->with(['classes' => function ($q) use ($teacher) {
                $q->whereHas('teachers', fn ($q2) => $q2->where('teachers.id', $teacher->id))
                ->select('id','lesson_id','start_time','end_time','day','capacity');
            }])
            ->get();

        return response()->json($lessons);
    }


}
