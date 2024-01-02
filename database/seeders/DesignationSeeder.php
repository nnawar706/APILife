<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('designations')->insert([
            ['name' => 'Wise Owl'],
            ['name' => 'Cuddly Manager'],
            ['name' => 'Panda Playmaker'],
            ['name' => 'Senior Panda'],
            ['name' => 'Fluffy Panda'],
            ['name' => 'Bamboo Ambassador'],
            ['name' => 'Mystic Stronghold'],
        ]);
    }
}
