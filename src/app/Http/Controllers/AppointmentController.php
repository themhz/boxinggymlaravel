<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Models\Program;
use App\Models\AppointmentAvailability;


class AppointmentController extends Controller
{
    public function index()
    {
        return response()->json(
            Appointment::with(['student', 'program.classType'])->get()
        );
    }

    public function show($id)
    {
        $appointment = Appointment::with(['student', 'program.classType'])->findOrFail($id);
        return response()->json($appointment);
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'program_id' => 'required|exists:programs,id',
            'availability_id' => 'required|exists:appointment_availability,id',
            'status' => 'nullable|in:booked,cancelled,attended',
            'notes' => 'nullable|string',
        ]);

        $studentId = $validated['student_id'];
        $programId = $validated['program_id'];
        $availabilityId = $validated['availability_id'];

        $program = Program::with('appointments')->findOrFail($programId);

        // ✅ Get the availability slot directly
        $availability = AppointmentAvailability::findOrFail($availabilityId);
        if (! $availability->is_available) {
            return response()->json(['message' => 'Selected time slot is not available.'], 409);
        }

        // 🧠 Prevent double booking by same student for same program
        $alreadyBooked = $program->appointments()
            ->where('student_id', $studentId)
            ->where('status', 'booked')
            ->exists();

        if ($alreadyBooked) {
            return response()->json(['message' => 'You have already booked this program.'], 409);
        }

        // 📆 Check program capacity
        $bookedCount = $program->appointments()->where('status', 'booked')->count();
        if ($bookedCount >= $program->capacity) {
            return response()->json(['message' => 'Program is full.'], 409);
        }

        // ⏰ Overlap check
        $conflict = Appointment::where('student_id', $studentId)
            ->whereHas('program', function ($q) use ($program) {
                $q->where('day', $program->day);
            })
            ->get()
            ->some(function ($appt) use ($program) {
                $p = $appt->program;
                return ($program->start_time < $p->end_time && $program->end_time > $p->start_time);
            });

        if ($conflict) {
            return response()->json(['message' => 'You have another appointment that overlaps.'], 409);
        }

        // ✅ Create the appointment
        $appointment = Appointment::create($validated);

        return response()->json([
            'message' => 'Appointment booked successfully',
            'appointment' => $appointment
        ], 201);
    }





    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'student_id' => 'sometimes|exists:students,id',
            'program_id' => 'sometimes|exists:programs,id',
            'status' => 'sometimes|in:booked,cancelled,attended',
            'notes' => 'nullable|string',
        ]);

        $appointment->update($validated);

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment
        ]);
    }

    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted']);
    }
}
