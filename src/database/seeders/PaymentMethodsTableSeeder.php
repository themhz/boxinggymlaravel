<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentMethod;


class PaymentMethodsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentMethod::insert([
            ['name' => 'credit_card', 'description' => 'Paid by card'],
            ['name' => 'cash', 'description' => 'Cash at front desk'],
            ['name' => 'bank_transfer', 'description' => 'Bank wire transfer'],
        ]);
    }
}
