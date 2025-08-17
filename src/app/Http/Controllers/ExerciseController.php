<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    public function index()
    {
        return Exercise::select('id','name','description','exercise_type')->get();
    }

    public function show($id)
    {
        $exercise = Exercise::findOrFail($id);

        return response()->json([
            'id'            => $exercise->id,
            'name'          => $exercise->name,
            'description'   => $exercise->description,
            'exercise_type' => $exercise->exercise_type,
        ]);
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'name'          => 'required|string|max:100|unique:exercises,name',
            'description'   => 'nullable|string|max:255',
            'exercise_type' => 'required|string|max:50', // e.g. strength, cardio, flexibility
        ]);

        $exercise = Exercise::create($data);

        return response()->json([
            'id'            => $exercise->id,
            'name'          => $exercise->name,
            'description'   => $exercise->description,
            'exercise_type' => $exercise->exercise_type,
        ], 201);
    }

    public function update(Request $req, $id)
    {
        $exercise = Exercise::findOrFail($id);

        $data = $req->validate([
            'name'          => 'sometimes|string|max:100|unique:exercises,name,' . $exercise->id,
            'description'   => 'nullable|string|max:255',
            'exercise_type' => 'sometimes|string|max:50',
        ]);

        $exercise->update($data);

        return response()->json([
            'id'            => $exercise->id,
            'name'          => $exercise->name,
            'description'   => $exercise->description,
            'exercise_type' => $exercise->exercise_type,
        ]);
    }

    public function destroy($id)
    {
        Exercise::findOrFail($id)->delete();
        return response()->json(['message' => 'Exercise deleted'], 200);
    }
}
