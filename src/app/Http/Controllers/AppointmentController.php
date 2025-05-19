<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Models\ClassModel;
use App\Models\AppointmentAvailability;


class AppointmentController extends Controller
{
    public function index()
    {
        return response()->json(
            Appointment::with(['student', 'class.classType'])->get()
        );
    }

    public function show($id)
    {
        $appointment = Appointment::with(['student', 'class.classType'])->findOrFail($id);
        return response()->json($appointment);
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:clases,id',
            'availability_id' => 'required|exists:appointment_availability,id',
            'status' => 'nullable|in:booked,cancelled,attended',
            'notes' => 'nullable|string',
        ]);

        $studentId = $validated['student_id'];
        $classId = $validated['class_id'];
        $availabilityId = $validated['availability_id'];

        $class = ClassModel::with('appointments')->findOrFail($classId);


        // ✅ Get the availability slot directly
        $availability = AppointmentAvailability::findOrFail($availabilityId);
        if (! $availability->is_available) {
            return response()->json(['message' => 'Selected time slot is not available.'], 409);
        }

        // 🧠 Prevent double booking by same student for same class
        $alreadyBooked = $class->appointments()
            ->where('student_id', $studentId)
            ->where('status', 'booked')
            ->exists();

        if ($alreadyBooked) {
            return response()->json(['message' => 'You have already booked this class.'], 409);
        }

        // 📆 Check class capacity
        $bookedCount = $class->appointments()->where('status', 'booked')->count();
        if ($bookedCount >= $class->capacity) {
            return response()->json(['message' => 'Class is full.'], 409);
        }

        // ⏰ Overlap check
        $conflict = Appointment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($class) {
                $q->where('day', $class->day);
            })
            ->get()
            ->some(function ($appt) use ($class) {
                $p = $appt->class;
                return ($class->start_time < $p->end_time && $class->end_time > $p->start_time);
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
            'class_id' => 'sometimes|exists:class,id',
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
