<?php

namespace App\Console\Commands;

use App\Models\EventRating;
use App\Models\User;
use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendRandomNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-random-notification';

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
        $quotes = [
            "Extravaganza planning: where we turn 'why not?' into 'oh wow!' 🎉",
            "Tip for crafting an event: Think big, dream bigger, and then add a touch of 'I-can't-believe-we-pulled-this-off 💥",
            'Extravaganza planning tip: When in doubt, add more sparkles, because ordinary is so last century 🥳',
            "Extravaganza planning rule #1: If it doesn't make you gasp in awe, you're not doing it right 💥",
            'Extravaganza planning tip: Start with a sprinkle of creativity, add a dash of audacity, and garnish with a generous helping of wow-factor 🥳',
            "It's Mickey. Why don't you come and play with us for a bit? 🐣",
        ];

        // select a random quote from the array
        $index = Carbon::now('Asia/Dhaka')->format('n') % 6;

        $start_date = Carbon::now('Asia/Dhaka')->subMonth(1);
        $end_date = Carbon::now('Asia/Dhaka');

        // fetch event that got the highest rating in previous month
        $event = EventRating::whereHas('event', function ($q) use ($start_date, $end_date) {
            return $q->whereBetween('created_at', [$start_date, $end_date]);
        })->orderByDesc('avg_rating')->first();

        $users = User::status()->get();

        foreach ($users as $user)
        {
            $msg = 'Hey ' . $user->name . ' 👋 ' . $quotes[$index];

            // send random quotes to users
            $user->notify(new UserNotification(
                '',
                $msg,
                null,
                'Life++',
                null
            ));

            // if event exists in previous month, notify users about it
            if ($event)
            {
                $msg2 = 'Hey ' . $user->name . ' 👋 ' . $event->event->title . ' got the highest rating this month.💥';

                $user->notify(new UserNotification(
                    '',
                    $msg2,
                    null,
                    'Life++',
                    null
                ));
            }
        }
    }
}
