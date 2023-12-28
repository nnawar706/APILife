<?php

namespace App\Console\Commands;

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

        foreach ($users as $item)
        {
            $birthdate = $item->birthday . '-' . Carbon::today('Asia/Dhaka')->format('Y');

            if (Carbon::today('Asia/Dhaka')->format('d-m-Y') == Carbon::parse($birthdate)->format('d-m-Y'))
            {
                $item->notify(new UserNotification('', 'Happy Birthday! 🎉 🎊 May all your dreams turn into reality.'));
            }
        }
    }
}
