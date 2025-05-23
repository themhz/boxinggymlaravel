<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    public function index()
    {
        $exercises = Exercise::paginate(20);
        return view('exercises.index', compact('exercises'));
    }

    public function create()
    {
        return view('exercises.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sets' => 'required|integer|min:1',
            'repetitions' => 'required|integer|min:1',
        ]);

        Exercise::create($data);

        return redirect()->route('exercises.index')
                         ->with('success', 'Exercise added.');
    }

    public function show(Exercise $exercise)
    {
        return view('exercises.show', compact('exercise'));
    }

    public function edit(Exercise $exercise)
    {
        return view('exercises.edit', compact('exercise'));
    }

    public function update(Request $request, Exercise $exercise)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sets' => 'required|integer|min:1',
            'repetitions' => 'required|integer|min:1',
        ]);

        $exercise->update($data);

        return redirect()->route('exercises.index')
                         ->with('success', 'Exercise updated.');
    }

    public function destroy(Exercise $exercise)
    {
        $exercise->delete();
        return redirect()->route('exercises.index')
                         ->with('success', 'Exercise removed.');
    }
}
