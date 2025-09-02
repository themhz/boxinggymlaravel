<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    // GET /api/appointments  (admin, if you protect it)
    public function index(): JsonResponse
    {
        $items = Appointment::latest('id')->get();

        return response()->json([
            'result' => 'success',
            'data'   => $items,
        ]);
    }

    // GET /api/appointments/{appointment}
    public function show(Appointment $appointment): JsonResponse
    {
        return response()->json([
            'result' => 'success',
            'data'   => $appointment,
        ]);
    }

    // POST /api/appointments
    // Books an appointment into a slot while enforcing capacity/is_captured
    public function store(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'slot_id' => 'required|exists:appointment_slots,id',
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:30',
            'notes'   => 'nullable|string',
        ]);

        // Use a transaction + row lock to avoid race conditions on popular slots
        $appointment = DB::transaction(function () use ($data) {
            
            $slot = AppointmentSlot::whereKey($data['slot_id'])->lockForUpdate()->first();

            // Guard capacity
            $bookedCount = $slot->appointments()->count();
            if ($slot->is_captured || $bookedCount >= $slot->capacity) {
                abort(response()->json([
                    'result'  => 'error',
                    'message' => 'The selected time slot is already fully booked.',
                ], 409));
            }

            // Create appointment
            $appointment = Appointment::create([
                'slot_id' => $slot->id,
                'name'    => $data['name'],
                'email'   => $data['email'] ?? null,
                'phone'   => $data['phone'] ?? null,
                'notes'   => $data['notes'] ?? null,
                'status'  => 'pending',
            ]);

            // Recompute capture flag after the new booking
            $isFullNow = $slot->appointments()->count() >= $slot->capacity;
            if ($slot->is_captured !== $isFullNow) {
                $slot->is_captured = $isFullNow;
                $slot->save();
            }

            return $appointment;
        });

        return response()->json([
            'result'  => 'success',
            'message' => 'Appointment created',
            'data'    => $appointment,
        ], 201);
    }

    // PUT/PATCH /api/appointments/{appointment}
    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            // allow editing of these fields
            'name'    => 'sometimes|required|string|max:255',
            'email'   => 'sometimes|nullable|email|max:255',
            'phone'   => 'sometimes|nullable|string|max:30',
            'notes'   => 'sometimes|nullable|string',
            'status'  => 'sometimes|in:pending,confirmed,cancelled',

            // allow moving to another slot
            'slot_id' => 'sometimes|required|exists:appointment_slots,id',
        ]);

        $updated = DB::transaction(function () use ($appointment, $data) {
            // Lock current slot (can be null in theory)
            $oldSlot = $appointment->slot()->lockForUpdate()->first();

            // If client requested a new slot, lock it and guard capacity
            $newSlot = null;
            if (array_key_exists('slot_id', $data)) {
                $requestedSlotId = (int) $data['slot_id'];
                $currentSlotId   = optional($oldSlot)->id;

                if ($requestedSlotId !== $currentSlotId) {
                    $newSlot = \App\Models\AppointmentSlot::whereKey($requestedSlotId)
                        ->lockForUpdate()
                        ->first();

                    // Capacity guard
                    $booked = $newSlot->appointments()->count();
                    if ($newSlot->is_captured || $booked >= $newSlot->capacity) {
                        abort(response()->json([
                            'result'  => 'error',
                            'message' => 'The selected time slot is already fully booked.',
                        ], 409));
                    }

                    // Move the appointment to the new slot
                    $appointment->slot_id = $newSlot->id;
                }
            }

            // Update other editable fields
            $other = $data;
            unset($other['slot_id']);
            if (!empty($other)) {
                $appointment->fill($other);
            }
            $appointment->save();

            // Recompute is_captured for involved slots
            foreach ([$oldSlot, $newSlot] as $slot) {
                if ($slot) {
                    $isFull = $slot->appointments()->count() >= $slot->capacity;
                    if ($slot->is_captured !== $isFull) {
                        $slot->is_captured = $isFull;
                        $slot->save();
                    }
                }
            }

            return $appointment->fresh();
        });

        return response()->json([
            'result'  => 'success',
            'message' => 'Appointment updated',
            'data'    => $updated,
        ]);
    }

    // DELETE /api/appointments/{appointment}
    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        DB::transaction(function () use ($appointment) {
            $slot = $appointment->slot()->lockForUpdate()->first();
            $appointment->delete();

            if ($slot) {
                // Capacity-aware recompute of is_captured
                $isFull = $slot->appointments()->count() >= $slot->capacity;
                if ($slot->is_captured !== $isFull) {
                    $slot->is_captured = $isFull;
                    $slot->save();
                }
            }
        });

        return response()->json([
            'result'  => 'success',
            'message' => 'Appointment deleted',
        ]);
    }
}
