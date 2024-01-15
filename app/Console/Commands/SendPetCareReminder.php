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
            $msg = "It's Pet Care Day! Ensure food, water, and hygiene of Mickey & Minnie!";

            $notification = Notification::where('data', 'like', '%' . $msg . '%')
                ->latest()->first();

            if (!$notification) {
                $user = User::first();
            } else {
                $user = User::where('id', '>', $notification->notifiable_id)
                    ->first();

                if (!$user) {
                    $user = User::first();
                }
            }

            $users = User::status()->get();

            foreach ($users as $item)
            {
                if ($item->id != $user->id)
                {
                    $msg = 'Hey ' . $item->name . ' 👋 ' . 'Remind ' . $user->name . ' to ensure food, water, and hygiene of Mickey & Minnie!' . '🦜';
                } else {
                    $msg = 'Hey ' . $user->name . ' 👋 ' . $msg . '🦜';
                }

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
