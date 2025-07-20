<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StudentPayment;
use App\Models\MembershipPlan;
use App\Models\Offer;
use App\Models\PaymentMethod;
use App\Models\Student;
use Illuminate\Support\Arr;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Ensure various payment methods exist
        $methods = [
            ['name' => 'cash', 'description' => 'Paid with cash'],
            ['name' => 'credit_card', 'description' => 'Paid via credit/debit card'],
            ['name' => 'bank_transfer', 'description' => 'Paid via bank transfer'],
            ['name' => 'paypal', 'description' => 'Paid via PayPal'],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['name' => $method['name']],
                ['description' => $method['description']]
            );
        }

        // ✅ Fetch required data
        $paymentMethodIds = PaymentMethod::pluck('id')->toArray();
        $membershipPlan = MembershipPlan::first();
        $offer = Offer::first();
        $students = Student::with('user')->inRandomOrder()->take(30)->get();

        // ✅ Create 30 payments
        foreach ($students as $student) {
            for ($i = 0; $i < 3; $i++) {
                $start = now()->subDays(rand(10, 60));
                $end = (clone $start)->addDays($membershipPlan->duration_days);

                StudentPayment::create([
                    'user_id' => $student->user_id,
                    'payment_method_id' => Arr::random($paymentMethodIds),
                    'membership_plan_id' => $membershipPlan->id,
                    'offer_id' => $offer->id,
                    'start_date' => $start,
                    'end_date' => $end,
                    'amount' => $membershipPlan->price,
                ]);
            }
        }
    }
}
