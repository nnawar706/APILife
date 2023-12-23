<?php

namespace App\Jobs;

use App\Models\Treasurer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TreasurerLiabilitiesCalculation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $treasurer_id;

    /**
     * Create a new job instance.
     */
    public function __construct($treasurer_id)
    {
        $this->treasurer_id = $treasurer_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
//        $treasure = Treasurer::find($this->treasurer_id);
//
//        if ($treasure)
//        {
//            $events =
//        }
    }
}
