<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserStory;
use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemoveUserStories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-user-stories';

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
        $deleteTime = Carbon::now('Asia/Dhaka')->addDay()->subHours(24);
        $curTime    = Carbon::now('Asia/Dhaka')->addDay();

        UserStory::where('created_at', '<', $deleteTime)->each(function ($story) {
            deleteFile($story->story_url);
            $story->delete();
        });

        $usersStreakEnded = User::whereDoesntHave('stories', function ($q) use ($curTime, $deleteTime) {
            return $q->whereBetween('created_at', [$deleteTime, $curTime]);
        })->get();

        foreach ($usersStreakEnded as $item)
        {
            if ($item->current_streak != 0)
            {
                if ($item->current_streak >= 5)
                {
                    $item->notify(new UserNotification(
                        '/pages/accounts/notification',
                        'Your streak for ' . $item->current_streak . ' days ended today. 🥺',
                        'Life++',
                        null
                    ));
                }

                $item->current_streak = 0;
                $item->saveQuietly();
            }
        }
    }
}
