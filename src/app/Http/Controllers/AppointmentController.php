<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Appointment;


class AppointmentController extends Controller
{
    /**
     * Display a listing of the appointments (admin only).
     */
    public function index()
    {
        return Appointment::all();
    }

    /**
     * Store a newly created appointment in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:30',
            'scheduled_at' => 'required|date',
            'notes'        => 'nullable|string',
        ]);

        $appointment = Appointment::create($validated);

        return response()->json($appointment, 201);
    }

    /**
     * Display the specified appointment.
     */
    public function show(Appointment $appointment)
    {
        return $appointment;
    }

    /**
     * Update the specified appointment in storage (e.g. confirm/cancel).
     */
    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'status'       => 'in:pending,confirmed,cancelled',
            'scheduled_at' => 'sometimes|date',
            'notes'        => 'sometimes|string|nullable',
        ]);

        $appointment->update($validated);

        return response()->json($appointment);
    }

    /**
     * Remove the specified appointment from storage.
     */
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted']);
    }
}
