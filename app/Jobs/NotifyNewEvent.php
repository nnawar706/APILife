<?php

namespace App\Jobs;

use App\Models\Event;
use App\Notifications\UserNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyNewEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $event;

    /**
     * Create a new job instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $participants = $this->event->participants()->get();

        $lead = $this->event->lead()->first();

        foreach ($participants as $item)
        {
            $item->notify(new UserNotification('pages/expense-calculator/extra-vaganza', 'New extravaganza has been created.'));
        }

        $lead->notify(new UserNotification('pages/expense-calculator/extra-vaganza', 'You have been selected as an extravaganza leader.'));
    }
}
