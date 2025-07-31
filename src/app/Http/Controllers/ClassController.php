<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ClassSession; 
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClassController extends Controller
{    
    public function index(): JsonResponse
    {     
        $classes = ClassModel::with(['lesson', 'teacher'])->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'day'        => $c->day,
                'start_time' => $c->start_time,
                'end_time'   => $c->end_time,
                'capacity'   => $c->capacity,

                'lesson'     => [
                    'id'          => $c->lesson->id,
                    'title'       => $c->lesson->title,
                    'description' => $c->lesson->description,
                    'image'       => $c->lesson->image,
                ],

                'teacher'    => [
                    'id'    => $c->teacher->id,
                    'name'  => $c->teacher->name,
                    'email' => $c->teacher->email,
                ]
            ]);

            return response()->json([
                'classes' => $classes,
            ]);
        
       
    }

    public function schedule(): JsonResponse
    {
        $classes = ClassModel::with(['lesson', 'teacher'])->get();

        $schedule = $classes->groupBy('day')
            ->mapWithKeys(function ($group, $day) {
                return [
                    $day => $group
                        ->sortBy('start_time') // sort by start_time asc within the day or $group->sortByDesc('start_time') for desc
                        ->map(fn($c) => [
                            'class'      => $c->lesson->title,
                            'start_time' => $c->start_time,
                            'end_time'   => $c->end_time,
                            'capacity'   => $c->capacity,
                            'teacher'    => $c->teacher->name ?? null,
                        ])
                        ->values()
                ];
            });

        return response()->json([
            'schedule' => $schedule,
        ]);
    }


    public function show($id)
    {
        $class = ClassModel::with(['lesson', 'teacher'])->findOrFail($id);

        return response()->json([
            'class' => $class
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'lesson_id'   => 'required|exists:lessons,id',
            'teacher_id'  => 'required|exists:teachers,id', // 👈 Add this
            'start_time'  => 'required|date_format:H:i:s',
            'end_time'    => 'required|date_format:H:i:s|after:start_time',
            'day'         => 'required|string',
            'capacity'    => 'required|integer|min:1',
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
            'teacher_id' => 'sometimes|exists:teachers,id', // ✅ Add this
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
        $class = ClassModel::find($id);

        if (!$class) {
            return response()->json(['deleted' => 0], 404);
        }

        $deleted = $class->delete(); // returns true if deleted

        return response()->json(['deleted' => $deleted ? 1 : 0]);
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

    // public function students(ClassModel $class)
    // {
    //     return response()->json($class->students);
    // }

    public function students($id)
    {
        $class = ClassModel::findOrFail($id);
        return response()->json($class->students);
    }


    // Add student to class
    public function addStudent(Request $request, $id)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $class = ClassModel::findOrFail($id);
        $class->students()->syncWithoutDetaching([$request->student_id]);

        return response()->json(['message' => 'Student added to class']);
    }

    // Update student pivot (optional)
    public function updateStudent(Request $request, $classId, $studentId)
    {
        try {
            $class = ClassModel::findOrFail($classId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['result' => 0, 'message' => 'Class not found'], 404);
        }

        $updated = $class->students()->updateExistingPivot($studentId, [
            'status' => $request->input('status'),
            'note'   => $request->input('note'),
        ]);

        return response()->json([
            'result' => $updated ? 1 : 0,
            'message' => $updated ? 'Student updated in class' : 'Nothing was updated',
        ]);
    }

    public function patchStudent(Request $request, $classId, $studentId)
    {
        try {
            $class = ClassModel::findOrFail($classId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['result' => 0, 'message' => 'Class not found'], 404);
        }

        $data = $request->only(['status', 'note']);
        if (empty($data)) {
            return response()->json(['result' => 0, 'message' => 'No fields provided'], 400);
        }

        $updated = $class->students()->updateExistingPivot($studentId, $data);

        return response()->json([
            'result' => $updated ? 1 : 0,
            'message' => $updated ? 'Student updated in class' : 'Nothing was updated',
        ]);
    }


    // Remove student from class
    public function removeStudent($classId, $studentId)
    {
        try {
            $class = ClassModel::findOrFail($classId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['result' => 0, 'message' => 'Class not found'], 404);
        }

        $detached = $class->students()->detach($studentId); // returns number of rows affected

        return response()->json([
            'result' => $detached ? 1 : 0,
            'message' => $detached ? 'Student removed from class' : 'Student not enrolled or already removed',
        ]);
    }


}
