<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\User;
use App\Models\PetCare;
use Illuminate\Console\Command;
use App\Notifications\UserNotification;

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
        // send notifications on work days only
        if (!Carbon::now('Asia/Dhaka')->isFriday())
        {
            // fetch last pet care attendee
            $lastAttendee = PetCare::orderByDesc('id')->first();

            // fetch users who are interested in Mickie-Minnie (Selopia & Nileema excluded)
            $userModel = User::status()->interested();

            // at the initial database stage(when no attendee found), select the first fetched user as attendee
            if (!$lastAttendee)
            {
                $user = $userModel->clone()->first();
            }
            // if attendee found, select the next user based on id as attendee
            else
            {
                $user = $userModel->clone()->where('id', '>', $lastAttendee->user_id)->first();

                if (!$user)
                {
                    // if no next user found, go back to first user to select as attendee
                    $user = $userModel->clone()->first();
                }
            }

            // create today's pet care instance
            PetCare::create([
                'user_id' => $user->id,
            ]);

            $users = $userModel->clone()->get();

            foreach ($users as $item)
            {
                // notify others to remind today's attendee
                if ($item->id != $user->id)
                {
                    $msg = 'Hey ' . $item->name . ' 👋 ' . 'Remind ' . $user->name . ' to ensure food, water, and hygiene of Mickey & Minnie!' . '🦜';
                }
                // notify attendee about today's duty
                else
                {
                    $msg = 'Hey ' . $user->name . ' 👋 ' . "It's Pet Care Day! Ensure food, water, and hygiene of Mickey & Minnie!" . '🦜';
                }

                $item->notify(new UserNotification(
                    'pages/accounts/notification',
                    $msg,
                    null,
                    'Life++',
                    null
                ));
            }
        }
    }
}
