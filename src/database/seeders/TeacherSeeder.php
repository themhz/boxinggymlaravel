<?php
// database/seeders/TeacherSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeacherSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        DB::table('teachers')->insert([
            [
                'name'       => 'John Doe',
                'email'      => 'john.doe@example.com',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Jane Smith',
                'email'      => 'jane.smith@example.com',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Mike Johnson',
                'email'      => 'mike.johnson@example.com',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
