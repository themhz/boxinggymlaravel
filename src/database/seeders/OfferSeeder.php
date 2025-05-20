<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OfferSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        DB::table('offers')->insert([
            [
                'membership_plan_id' => null, // standalone offer
                'title'              => 'Student Discount',
                'description'        => 'Get 10% off any membership plan with a valid student ID.',
                'discount_percent'   => 10.00,
                'discount_amount'    => null,
                'starts_at'          => $now->copy()->subDays(7)->toDateString(), // started a week ago
                'ends_at'            => $now->copy()->addDays(30)->toDateString(), // runs for another month
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'membership_plan_id' => null,
                'title'              => 'Family Plan',
                'description'        => 'Sign up with a family member and each get 15% off your membership.',
                'discount_percent'   => 15.00,
                'discount_amount'    => null,
                'starts_at'          => $now->toDateString(),
                'ends_at'            => $now->copy()->addDays(60)->toDateString(),
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                // Tie this offer to the Premium Plan (assuming its ID is 3)
                'membership_plan_id' => 3,
                'title'              => 'Premium Early Bird',
                'description'        => 'Save $20 when you pre-pay your Premium Plan before next month.',
                'discount_percent'   => null,
                'discount_amount'    => 20.00,
                'starts_at'          => $now->copy()->addDays(1)->toDateString(),
                'ends_at'            => $now->copy()->addDays(15)->toDateString(),
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        ]);
    }
}
