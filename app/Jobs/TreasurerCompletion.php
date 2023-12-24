<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        if ($this->treasurerLiability
            ->treasurer->liabilities()
            ->where('status', false)->doesntExist())
        {
            $this->treasurerLiability->treasurer->completion_status = 1;
            $this->treasurerLiability->treasurer->save();
        }
    }
}
