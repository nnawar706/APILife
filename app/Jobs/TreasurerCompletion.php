<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TreasurerCompletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $treasurerLiability;

    /**
     * Create a new job instance.
     */
    public function __construct($treasurerLiability)
    {
        $this->treasurerLiability = $treasurerLiability;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // check if this treasurer has any liability where status is pending
        if ($this->treasurerLiability
            ->treasurer->liabilities()
            ->where('status', false)->doesntExist())
        {
            // if not, mark the treasure hunt as complete
            $this->treasurerLiability->treasurer->completion_status = 1;
            $this->treasurerLiability->treasurer->save();

            // fetch the events associated with this treasure hunt
            $events = $this->treasurerLiability->treasurer->events()->get();

            // mark all events as complete
            foreach ($events as $item)
            {
                $item->event->event_status_id = 4;
                $item->event->save();
            }
        }
    }
}
