<?php

namespace App\Jobs;

use App\Models\UserStory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompressUserStoryVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $story, $extension;

    /**
     * Create a new job instance.
     */
    public function __construct(UserStory $story, $extension)
    {
        $this->story     = $story;
        $this->extension = $extension;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // get actual video file path
        $inputVideoPath = public_path($this->story->story_url);

        // compressed video name
        $videoName = time() . rand(100, 9999) . '.' . $this->extension;

        // compressed video file path
        $outputVideoPath = public_path('/videos/user_stories/' . $videoName);

        // command to compress original video file and save it to generated file path
        $ffmpegCommand = "ffmpeg -i $inputVideoPath -vf scale=1280:-1 -c:v libx264 -preset slow -crf 24 $outputVideoPath";

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
