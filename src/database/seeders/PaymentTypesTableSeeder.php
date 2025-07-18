<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('payment_types')->insert([
            ['name' => 'subscription', 'description' => 'Student pays for access'],
            ['name' => 'salary', 'description' => 'Payment to teacher'],
            ['name' => 'bonus', 'description' => 'Extra or one-time payment'],
        ]);
    }
}
