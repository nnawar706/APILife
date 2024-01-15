<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPetCareReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-pet-care-reminder';

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
        if (!Carbon::now('Asia/Dhaka')->isFriday())
        {
            $users = User::status()->get();

            $message = "It's Pet Care Day! Ensure food, water, and hygiene of Mickey & Minnie!";

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
}
