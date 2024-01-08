<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'designation_id' => 1,
            'name'           => 'Selopia',
            'phone_no'       => '01700000000',
            'birthday'       => Carbon::now(),
            'member_since'   => Carbon::now(),
            'password'       => Hash::make('123456')
        ]);
    }
}
