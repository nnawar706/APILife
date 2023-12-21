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
            ['name' => 'Admin', 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Senior Developer', 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Mid-Junior Developer', 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Junior Developer', 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Intern', 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')],
        ]);
    }
}
