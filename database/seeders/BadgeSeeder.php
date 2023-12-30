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
            'name'      => 'Escapade Rookie',
            'image_url' => '/images/badges/654788245356.png'
        ]);

        Badge::updateOrCreate([
            'id' => 2
        ],[
            'name'      => 'Noisemaker',
            'image_url' => '/images/badges/973123468712.png'
        ]);

        Badge::updateOrCreate([
            'id' => 3
        ],[
            'name'      => 'Festivity Devotee',
            'image_url' => '/images/badges/854741240089.png'
        ]);

        Badge::updateOrCreate([
            'id' => 4
        ],[
            'name'      => 'Skylarker',
            'image_url' => '/images/badges/195441244350.png'
        ]);

        Badge::updateOrCreate([
            'id' => 5
        ],[
            'name'      => 'Extravaganza Overlord',
            'image_url' => '/images/badges/195441937150.png'
        ]);
    }
}
