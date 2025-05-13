<?php

namespace App\Http\Controllers;

use App\Models\AppointmentAvailability;

class AvailabilityController extends Controller
{
    public function index()
    {
        $availability = AppointmentAvailability::where('is_available', true)
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

        return response()->json($availability);
    }
}
