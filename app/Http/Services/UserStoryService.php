<?php

namespace App\Http\Services;

use App\Models\UserStory;
use Illuminate\Http\Request;

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

    public function storeStory(Request $request): void
    {
        $story = $this->model->create([
            'user_id' => auth()->user()->id,
        ]);

        saveImage($request->image, '/images/user_stories/', $story, 'story_url');
    }

    public function markStoryAsSeen($id): void
    {
        $story = $this->model->findOrFail($id);

        $story->views()->firstOrCreate([
            'seen_by' => auth()->user()->id
        ]);
    }
}
