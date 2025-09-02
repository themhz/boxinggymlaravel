<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\AppointmentSlot;

class AppointmentsTableSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch the first four slots (assumes slots have been created)
        $bookedSlots = AppointmentSlot::orderBy('start_time')->take(4)->get();

        foreach ($bookedSlots as $slot) {
            Appointment::create([
                'slot_id' => $slot->id,
                'name'    => 'Demo User for slot '.$slot->id,
                'email'   => 'demo'.$slot->id.'@example.com',
                'phone'   => '555-000'.$slot->id,
                'notes'   => 'Pre‑seeded booking',
                'status'  => 'confirmed',
            ]);

             // mark the slot as captured
            $slot->is_captured = true;
            $slot->save();
        }
    }
}
