<?php

namespace App\Http\Services;

use App\Jobs\CompressUserStoryVideo;
use App\Models\UserStory;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Owenoj\LaravelGetId3\GetId3;

class UserStoryService
{
    private $model;

    public function __construct(UserStory $model)
    {
        $this->model = $model;
    }

    public function getAllUnseenStories()
    {
        // get time that is 6 hours ago from now
        $six_hours_ago = Carbon::now('Asia/Dhaka')->subHours(6)->format('Y-m-d H:i:s');

        return $this->model
            ->whereDoesntHave('views', function ($q) use ($six_hours_ago) {
            // auth user can see stories for 6 hours
            return $q->where('seen_by', '=', auth()->user()->id)
                ->where('created_at', '<', $six_hours_ago);
        })->with(['uploadedByInfo' => function ($q) {
            // fetch id, name, & photo url only
            return $q->select('id','name','photo_url');
        }])->with(['viewers' => function ($q) {
            // fetch name and photo url only
            return $q->select('users.id','user_story_id','seen_by','name','photo_url');
        }])->latest()->get();
    }

    public function storeStory(Request $request)
    {
        try {
            $file = $request->file('file');

            $duration = 0;

            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, ['jpeg','jpg','png','gif']))
            {
                $video = new GetId3($file);

                $duration = $video->getPlaytimeSeconds();

                $videoName = 'tmp_' . time() . rand(100, 999) . '.' . $extension;

                $file->move(public_path('/videos/user_stories/'), $videoName);

                $url = '/videos/user_stories/' . $videoName;
            }
            else if ($extension == 'gif') {
                $imageName = time() . rand(100, 9999) . '.' . $extension;
                $file->move(public_path('/images/user_stories/'), $imageName);

                $url = '/images/user_stories/' . $imageName;
            }
            else {
                $storyImage = Image::make($file);

                $compressedStoryImage = $storyImage->encode('jpg')->orientate()
                    ->resize(1200, 1200, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                $imageName = time() . rand(100, 9999) . '.jpg';
                $compressedStoryImage->save(public_path('/images/user_stories/' . $imageName));

                $url = '/images/user_stories/' . $imageName;
            }

            $story = $this->model->create([
                'user_id'   => auth()->user()->id,
                'story_url' => $url
            ]);

            if (!in_array($extension, ['jpeg','jpg','png','gif']))
            {
                $story->duration = $duration;
                $story->saveQuietly();

                dispatch(new CompressUserStoryVideo($story));
            }

            return null;
        } catch (\Throwable $th)
        {
            return $th->getMessage();
        }
    }

    public function markStoryAsSeen($id): bool
    {
        $story = $this->model->findOrFail($id);

        try {
            $view = $story->views()->firstOrCreate([
                'seen_by' => auth()->user()->id
            ]);

            return $view->wasRecentlyCreated;
        } catch (QueryException $ex) {
            return false;
        }
    }

    public function getAuthUnseenStoryCount()
    {
        return $this->model->whereDoesntHave('views', function ($q) {
            return $q->where('seen_by', '=', auth()->user()->id);
        })->count();
    }

    public function getLastStoryCreationTime()
    {
        // get time that is 6 hours ago from now
        $six_hours_ago = Carbon::now('Asia/Dhaka')->subHours(6)->format('Y-m-d H:i:s');

        $lastNotification = $this->model
            ->whereDoesntHave('views', function ($q) use ($six_hours_ago) {
                // auth user can see stories for 6 hours
                return $q->where('seen_by', '=', auth()->user()->id)
                    ->where('created_at', '<', $six_hours_ago);
            })->latest()->first();

        return $lastNotification ? $lastNotification->created_at : null;
    }
}
