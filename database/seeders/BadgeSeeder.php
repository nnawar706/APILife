<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Badge::updateOrCreate([
            'id' => 1
        ],[
            'name'      => 'Badge 1',
            'image_url' => '/images/badges/654788245356.png'
        ]);

        Badge::updateOrCreate([
            'id' => 2
        ],[
            'name'      => 'Badge 2',
            'image_url' => '/images/badges/973123468712.png'
        ]);

        Badge::updateOrCreate([
            'id' => 3
        ],[
            'name'      => 'Badge 3',
            'image_url' => '/images/badges/854741240089.png'
        ]);
    }
}
