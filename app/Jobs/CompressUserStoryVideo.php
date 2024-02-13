<?php

namespace App\Jobs;

use App\Models\UserStory;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CompressUserStoryVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $story;

    /**
     * Create a new job instance.
     */
    public function __construct(UserStory $story)
    {
        $this->story     = $story;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // get actual video file path
        $inputVideoPath = public_path($this->story->story_url);

        // compressed video name
        $videoName = time() . rand(100, 9999) . '.mp4';

        // compressed video file path
        $outputVideoPath = public_path('/videos/user_stories/' . $videoName);

        $ffmpegCommand = "ffmpeg -i $inputVideoPath -c:v libx264 -c:a aac -vf scale=1280:-2 -movflags +faststart $outputVideoPath";
        // execute the compression command
        exec($ffmpegCommand);

        // delete the original video file
        deleteFile($this->story->story_url);

        // save the user story url with the compressed video file path
        $this->story->story_url = '/videos/user_stories/' . $videoName;
        // saving quietly to avoid triggering any events
        $this->story->saveQuietly();
    }
}
