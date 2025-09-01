<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         Appointment::factory()->create([
            'name'         => 'Demo User',
            'email'        => 'demo@example.com',
            'phone'        => '1234567890',
            'scheduled_at' => Carbon::now()->addDays(2),
            'notes'        => 'First trial session',
            'status'       => 'pending',
        ]);


        Appointment::factory()->create([
            'name'         => 'Demo User2',
            'email'        => 'demo@example2.com',
            'phone'        => '1234567330',
            'scheduled_at' => Carbon::now()->addDays(2),
            'notes'        => 'Second trial session',
            'status'       => 'pending',
        ]);

        Appointment::factory()->create([
            'name'         => 'Demo User3',
            'email'        => 'demo@adsada.com',
            'phone'        => '1234567330',
            'scheduled_at' => Carbon::now()->addDays(2),
            'notes'        => 'Third trial session',
            'status'       => 'completed',
        ]);
    }
}
