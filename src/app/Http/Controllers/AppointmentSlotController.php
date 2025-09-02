<?php

namespace App\Http\Controllers;

use App\Models\AppointmentSlot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;


class AppointmentSlotController extends Controller
{
    // GET /api/appointment-slots
    public function index(): JsonResponse
    {
        $items = AppointmentSlot::orderBy('start_time')->get();

        return response()->json([
            'result' => 'success',
            'data'   => $items,
        ]);
    }

    // GET /api/appointment-slots/{appointmentSlot}
    public function show(AppointmentSlot $appointmentSlot): JsonResponse
    {
        return response()->json([
            'result' => 'success',
            'data'   => $appointmentSlot,
        ]);
    }

    // POST /api/appointment-slots
    public function store(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'start_time'  => 'required|date',
            'end_time'    => 'required|date|after:start_time',
            'capacity'    => 'nullable|integer|min:1',
            'is_captured' => 'nullable|boolean',
        ]);

        $slot = AppointmentSlot::create([
            'start_time'  => $data['start_time'],
            'end_time'    => $data['end_time'],
            'capacity'    => $data['capacity'] ?? 1,            
            'is_captured' => $data['is_captured'] ?? false,
            'created_by'  =>  $request->user()?->id,
        ]);

        return response()->json([
            'result'  => 'success',
            'message' => 'Slot created',
            'data'    => $slot,
        ], 201);
    }

    // PUT/PATCH /api/appointment-slots/{appointmentSlot}
    public function update(Request $request, AppointmentSlot $appointmentSlot): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'start_time'  => 'sometimes|required|date',
            'end_time'    => 'sometimes|required|date|after:start_time',
            'capacity'    => 'sometimes|required|integer|min:1',
            'is_captured' => 'sometimes|boolean',
        ]);

        $slot = DB::transaction(function () use ($data, $appointmentSlot) {
            // Lock row to avoid races
            $slot = AppointmentSlot::whereKey($appointmentSlot->id)->lockForUpdate()->first();

            // Prevent shrinking capacity below current bookings
            if (array_key_exists('capacity', $data)) {
                $currentBooked = $slot->appointments()->count();
                if ($data['capacity'] < $currentBooked) {
                    abort(response()->json([
                        'result'  => 'error',
                        'message' => "Capacity can't be less than current bookings ($currentBooked).",
                    ], 422));
                }
            }

            // Fill all except is_captured (we’ll decide that next)
            $slot->fill(Arr::except($data, ['is_captured']));

            // If client asked explicitly, honor it; otherwise compute from bookings vs capacity
            if (array_key_exists('is_captured', $data)) {
                $slot->is_captured = (bool) $data['is_captured'];
            } else {
                $slot->is_captured = $slot->appointments()->count() >= $slot->capacity;
            }

            $slot->save();

            return $slot->fresh();
        });

        return response()->json([
            'result'  => 'success',
            'message' => 'Slot updated',
            'data'    => $slot,
        ]);
    }

    // DELETE /api/appointment-slots/{appointmentSlot}
    public function destroy(Request $request, AppointmentSlot $appointmentSlot): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        // Optional: block deletion if there are appointments in this slot
        if ($appointmentSlot->appointments()->exists()) {
            return response()->json([
                'result'  => 'error',
                'message' => 'Cannot delete a slot with existing appointments.',
            ], 409);
        }

        $appointmentSlot->delete();

        return response()->json([
            'result'  => 'success',
            'message' => 'Slot deleted',
        ]);
    }
}
