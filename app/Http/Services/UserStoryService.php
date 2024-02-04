<?php

namespace App\Http\Services;

use App\Models\UserStory;
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
        }])->latest()->get();
    }

    public function storeStory(Request $request)
    {
        try {
            $storyImage = Image::make($request->image);

            $compressedStoryImage = $storyImage->orientate()
                ->resize(1200, 1200, function ($constraint) {
                    $constraint->aspectRatio();
                });

            $imageName = time() . rand(100, 9999) . '.' . $request->image->getClientOriginalExtension();
            $compressedStoryImage->save(public_path('/images/user_stories/' . $imageName));

            $this->model->create([
                'user_id' => auth()->user()->id,
                'story_url' => '/images/user_stories/' . $imageName
            ]);
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