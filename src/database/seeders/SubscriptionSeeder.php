<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subscription;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Subscription::insert([
            ['name' => 'Monthly Plan', 'price' => 30.00, 'duration_days' => 30, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Quarterly Plan', 'price' => 85.00, 'duration_days' => 90, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Annual Plan', 'price' => 300.00, 'duration_days' => 365, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
