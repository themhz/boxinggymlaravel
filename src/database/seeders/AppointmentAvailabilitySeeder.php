<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppointmentAvailability;

class AppointmentAvailabilitySeeder extends Seeder
{
    public function run()
    {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $hours = ['09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00'];

        foreach ($days as $day) {
            foreach ($hours as $time) {
                AppointmentAvailability::create([
                    'day' => $day,
                    'start_time' => $time,
                    'is_available' => true,
                ]);
            }
        }
    }
}
