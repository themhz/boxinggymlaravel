<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::factory()->count(20)->create();

        // Create a specific user
        User::create([
            'name' => 'themhz',
            'email' => 'themhz@gmail.com',
            'password' => Hash::make('526996'), // Always hash passwords!
        ]);
    }
}
