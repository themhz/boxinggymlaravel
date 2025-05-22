<?php

namespace App\Http\Controllers;

use App\Models\ClassException;
use Illuminate\Http\Request;

class ClassExceptionController extends Controller
{
    public function index()
    {
        $exceptions = ClassException::with('class.lesson')->paginate(20);
        return view('exceptions.index', compact('exceptions'));
    }

    public function create()
    {
        $classes = \App\Models\ClassModel::all();
        return view('exceptions.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id'            => 'required|exists:classes,id',
            'exception_date'      => 'required|date',
            'is_cancelled'        => 'boolean',
            'override_start_time' => 'nullable|date_format:H:i:s',
            'override_end_time'   => 'nullable|date_format:H:i:s',
        ]);

        ClassException::create($data);

        return redirect()->route('exceptions.index')
                         ->with('success', 'Exception added.');
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

    public function destroy(ClassException $exception)
    {
        $exception->delete();
        return redirect()->route('exceptions.index')
                         ->with('success', 'Exception removed.');
    }
}
