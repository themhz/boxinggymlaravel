<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\User;
use App\Models\MembershipPlan;
use App\Models\Offer;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();        
        $membershipPlan = MembershipPlan::first(); // you need this
        $Offer = Offer::first(); // you need this

        if ($user && $membershipPlan) {
            $start = now();
            $end = $start->copy()->addDays($membershipPlan->duration_days);

            \App\Models\Payment::create([
                'user_id' => $user->id,                
                'membership_plan_id' => $membershipPlan->id, // ✅ required
                'offer_id' => $Offer->id, // ✅ required
                'start_date' => $start,
                'end_date' => $end,
                'amount' => $membershipPlan->price,
            ]);
        }
    }
}
