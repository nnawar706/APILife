<?php

namespace App\Console\Commands;

use App\Jobs\NotifyUsers;
use App\Models\User;
use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class WishHappyBirthday extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:wish-happy-birthday';

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
        $users = User::status()->get();

        $userIds = array_column(json_decode($users, true), 'id');

        foreach ($users as $item)
        {
            $birthdate = $item->birthday . '-' . Carbon::today('Asia/Dhaka')->format('Y');

            if (Carbon::today('Asia/Dhaka')->format('d-m-Y') == Carbon::parse($birthdate)->format('d-m-Y'))
            {
                $item->notify(new UserNotification(
                    '',
                    'Happy Birthday! 🎉 🎊 May all your dreams turn into reality.',
                    null,
                    null
                ));

                $notifyUsers = array_diff($userIds, [$item->id]);

                dispatch(new NotifyUsers(
                    $notifyUsers,
                    false,
                    'pages/accounts/notification',
                    "Today is " . $item->name ."'s day! 🎈🎁 Wish Happy Birthday before it's too late.",
                    null
                ));
            }

            if (Carbon::today('Asia/Dhaka')->format('d-m-Y') == Carbon::parse($birthdate)->subWeek(1)->format('d-m-Y'))
            {
                $notifyUsers = array_diff($userIds, [$item->id]);

                dispatch(new NotifyUsers(
                    $notifyUsers,
                    false,
                    'pages/accounts/notification',
                    $item->name ."'s birthday is coming next week! 🎈🎁 Let's start planning for the surprise.",
                    null
                ));
            }
        }
    }
}
