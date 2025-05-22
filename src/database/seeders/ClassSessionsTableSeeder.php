<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassSession;
use Carbon\Carbon;

class ClassSessionsTableSeeder extends Seeder
{
    public function run()
    {
        // Example session for Class #1 tomorrow
        ClassSession::create([
            'class_id'    => 1,
            'session_date'=> Carbon::now()->addDay(),
        ]);

        ClassSession::create([
            'class_id'    => 2,
            'session_date'=> Carbon::now()->addDay(),
        ]);
        
        ClassSession::create([
            'class_id'    => 3,
            'session_date'=> Carbon::now()->addDay(),
        ]);
    }
}