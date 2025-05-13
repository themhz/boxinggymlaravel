<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppointmentAvailability;

class AppointmentAvailabilityController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(\App\Models\AppointmentAvailability::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'day' => 'required|string', // e.g. "Tuesday"
            'start_time' => 'required|date_format:H:i:s', // e.g. "18:00:00"
            'is_available' => 'required|boolean',
        ]);

        $availability = AppointmentAvailability::create($validated);

        return response()->json([
            'message' => 'Availability created successfully',
            'availability' => $availability
        ], 201);
    }
}
