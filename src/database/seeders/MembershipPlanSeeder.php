<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MembershipPlanSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        DB::table('membership_plans')->insert([
            [
                'name'          => 'Basic Plan',
                'description'   => 'Access to 1 class per week, open gym hours, locker access.',
                'price'         => 50.00,
                'duration_days' => 30,   // 1 month
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name'          => 'Standard Plan',
                'description'   => 'Access to 3 classes per week, open gym hours, locker access, free gym T-shirt.',
                'price'         => 80.00,
                'duration_days' => 30,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name'          => 'Premium Plan',
                'description'   => 'Unlimited classes, open gym hours, locker access, free T-shirt, plus one personal training session per month.',
                'price'         => 120.00,
                'duration_days' => 30,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name'          => 'Annual Membership',
                'description'   => 'Full-year access to all classes with a discount.',
                'price'         => 499.99,
                'duration_days' => 365,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ]);
    }
}
