<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EndUserStoryStreak extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:end-user-story-streak';

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
        $prevTime = Carbon::now('Asia/Dhaka')->subHours(24);
        $curTime  = Carbon::now('Asia/Dhaka');

        User::whereDoesntHave('stories', function ($q) use ($curTime, $prevTime) {
            return $q->whereBetween('created_at', [$prevTime, $curTime]);
        })->each(function ($item) {
            if ($item->current_streak != 0)
            {
                if ($item->current_streak >= 5)
                {
                    $item->notify(new UserNotification(
                        '/pages/accounts/notification',
                        'Your streak for ' . $item->current_streak . ' days ended today. 🥺',
                        null,
                        'Life++',
                        null
                    ));
                }

                $item->current_streak = 0;
                $item->saveQuietly();
            }
        });
    }
}
