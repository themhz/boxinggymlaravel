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
        // ensure payment methods
        $methods = [
            ['name' => 'cash',          'description' => 'Paid with cash'],
            ['name' => 'credit_card',   'description' => 'Paid via credit/debit card'],
            ['name' => 'bank_transfer', 'description' => 'Paid via bank transfer'],
            ['name' => 'paypal',        'description' => 'Paid via PayPal'],
        ];
        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(['name' => $method['name']], ['description' => $method['description']]);
        }

        $paymentMethodIds = PaymentMethod::pluck('id')->toArray();
        $membershipPlan   = MembershipPlan::first();
        $offer            = Offer::first();

        $students = Student::inRandomOrder()->take(30)->get();

        foreach ($students as $student) {
            for ($i = 0; $i < 3; $i++) {
                $start = now()->subDays(rand(10, 60));
                $end   = (clone $start)->addDays(optional($membershipPlan)->duration_days ?? 30);

                StudentPayment::create([
                    'student_id'        => $student->id,                       // ⬅️ CHANGED
                    'payment_method_id' => Arr::random($paymentMethodIds),
                    'membership_plan_id'=> optional($membershipPlan)->id,
                    'offer_id'          => optional($offer)->id,
                    'start_date'        => $start->toDateString(),
                    'end_date'          => $end->toDateString(),
                    'amount'            => optional($membershipPlan)->price ?? 50.00,
                ]);
            }
        }
    }
}
