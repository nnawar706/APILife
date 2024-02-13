<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\EventInventory;
use Illuminate\Console\Command;
use App\Notifications\UserNotification;

class RemindEventInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remind-event-inventory';

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
        // fetch the approved inventories of events that will be held tomorrow
        $eventsTomorrow = EventInventory::whereHas('event', function ($q) {
            return $q->whereDate('from_date', Carbon::today('Asia/Dhaka')->addDay());
        })->approved()->get();

        // remind each user about their assigned inventory item
        foreach ($eventsTomorrow as $item)
        {
            $item->assignedToInfo->notify(new UserNotification(
                'pages/update-vaganza/' . $item->event_id,
                "Don't forget to bring " . $item->title . ' for ' . $item->event->title . '.',
                null,
                'Life++',
                null
            ));
        }
    }
}
