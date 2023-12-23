<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EventPaymentCalculation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $event_id;

    /**
     * Create a new job instance.
     */
    public function __construct($event_id)
    {
        $this->event_id = $event_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $event_data =
    }
}
