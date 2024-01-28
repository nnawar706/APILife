<?php

namespace App\Console\Commands;

use App\Models\PetCare;
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
            $lastAttendee = PetCare::orderByDesc('id')->first();

            $userModel = User::status()->interested();

            if (!$lastAttendee)
            {
                $user = $userModel->clone()->first();
            }
            else
            {
                $user = $userModel->clone()->where('id', '>', $lastAttendee->user_id)->first();

                if (!$user)
                {
                    $user = $userModel->clone()->first();
                }
            }

            PetCare::create([
                'user_id' => $user->id,
            ]);

            $users = $userModel->clone()->get();

            foreach ($users as $item)
            {
                if ($item->id != $user->id)
                {
                    $msg = 'Hey ' . $item->name . ' 👋 ' . 'Remind ' . $user->name . ' to ensure food, water, and hygiene of Mickey & Minnie!' . '🦜';
                }
                else
                {
                    $msg = 'Hey ' . $user->name . ' 👋 ' . "It's Pet Care Day! Ensure food, water, and hygiene of Mickey & Minnie!" . '🦜';
                }

                $item->notify(new UserNotification(
                    'pages/accounts/notification',
                    $msg,
                    'Life++',
                    null
                ));
            }
        }
    }
}
