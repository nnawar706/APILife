<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('event_statuses')->insert([
            ['name' => 'Ongoing'],
            ['name' => 'Locked'],
            ['name' => 'Lock Approved'],
            ['name' => 'Complete']
        ]);
    }
}
