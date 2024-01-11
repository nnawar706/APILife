<?php

namespace App\Console\Commands;

use App\Jobs\NotifyUsers;
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
            "Plan a party in such a way that everyone asks 'who is the host?' and you get to say 'thats a million dollar question' 🤓",
            "It's Pet Care Day! Ensure food, water, and hygiene of Mickey & Minnie! 🦜",
        ];

        // select a random quote
        $index = 7;

        $message = $quotes[$index];

        $users = User::status()->get();

        foreach ($users as $user)
        {
            $msg = 'Hey ' . $user->name . ' 👋 ' . $message;

            $user->notify(new UserNotification(
                '',
                $msg,
                'Life++',
                null
            ));
        }
    }
}
