<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppointmentSlot;
use Carbon\Carbon;

class AppointmentSlotsTableSeeder extends Seeder
{
    public function run(): void
    {
        $start = Carbon::now()->addDay()->startOfDay()->setTime(8, 0);

        for ($i = 0; $i < 10; $i++) {
            AppointmentSlot::create([
                'start_time' => $start->copy()->addHours($i),
                'end_time'   => $start->copy()->addHours($i + 1),                
                'capacity'   => 1,          
                'is_captured'  => false,      
                'created_by' => null, // or set the admin ID
            ]);
        }
    }
}
