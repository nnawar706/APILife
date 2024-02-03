<?php

namespace App\Console\Commands;

use App\Models\PetCare;
use App\Models\UserStory;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PruneUnnecessaryPetCareModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-unnecessary-pet-care-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lastPetCare = PetCare::latest()->first();

        if ($lastPetCare)
        {
            // delete all pet care entries monthly except the last one
            PetCare::whereNot('id', $lastPetCare->id)->delete();
        }

        UserStory::where('created_at', '<', Carbon::now('Asia/Dhaka')->subMonths(2))->delete();
    }
}
