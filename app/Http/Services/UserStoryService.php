<?php

namespace App\Http\Services;

use App\Jobs\CompressUserStoryVideo;
use App\Models\UserStory;
use FFMpeg\FFMpeg;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class UserStoryService
{
    private $model;

    public function __construct(UserStory $model)
    {
        $this->model = $model;
    }

    public function getAllUnseenStories()
    {
        return $this->model->whereDoesntHave('views', function ($q) {
            return $q->where('seen_by', '=', auth()->user()->id);
        })->with(['uploadedByInfo' => function ($q) {
            return $q->select('id','name','photo_url');
        }])->with(['viewers' => function ($q) {
            return $q->select('users.id','user_story_id','seen_by','name','photo_url');
        }])->latest()->get();
    }

    public function storeStory(Request $request)
    {
        try {
            $file = $request->file('file');

            $extension = $file->getClientOriginalExtension();

            if ($extension == 'mp4')
            {
                $videoName = 'tmp_' . time() . rand(100, 999) . '.' . $extension;

                $file->move(public_path('/videos/user_stories/'), $videoName);

                $url = '/videos/user_stories/' . $videoName;
            } else {
                $storyImage = Image::make($file);

                $compressedStoryImage = $storyImage->orientate()
                    ->resize(1200, 1200, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                $imageName = time() . rand(100, 9999) . '.' . $extension;
                $compressedStoryImage->save(public_path('/images/user_stories/' . $imageName));

                $url = '/images/user_stories/' . $imageName;
            }

            $story = $this->model->create([
                'user_id'   => auth()->user()->id,
                'story_url' => $url
            ]);

            if ($extension == 'mp4')
            {
                dispatch(new CompressUserStoryVideo($story, $extension));
            }

            return null;
        } catch (\Throwable $th)
        {
            return $th->getMessage();
        }
    }

    public function markStoryAsSeen($id): void
    {
        $story = $this->model->findOrFail($id);

        $story->views()->firstOrCreate([
            'seen_by' => auth()->user()->id
        ]);
    }
}
