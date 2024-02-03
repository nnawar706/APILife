<?php

namespace App\Console\Commands;

use App\Models\UserStory;
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
        $deleteTime = Carbon::now('Asia/Dhaka')->subHours(24);

        UserStory::where('created_at', '<', $deleteTime)->each(function ($story) {
            deleteFile($story->story_url);
            $story->delete();
        });
    }
}
