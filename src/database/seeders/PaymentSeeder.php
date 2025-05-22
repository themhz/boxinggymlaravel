<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\User;
use App\Models\Subscription;
use App\Models\MembershipPlan;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $subscription = Subscription::first();
        $membershipPlan = MembershipPlan::first(); // you need this

        if ($user && $subscription && $membershipPlan) {
            $start = now();
            $end = $start->copy()->addDays($membershipPlan->duration_days);

            \App\Models\Payment::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'membership_plan_id' => $membershipPlan->id, // ✅ required
                'start_date' => $start,
                'end_date' => $end,
                'amount' => $membershipPlan->price,
            ]);
        }
    }
}
