<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoryReactionRequest;
use App\Http\Services\UserStoryService;
use App\Http\Requests\UserStoryCreateRequest;
use Symfony\Component\HttpFoundation\Response;

class UserStoryController extends Controller
{
    private $service;

    public function __construct(UserStoryService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getAllUnseenStories();
        $storyCount = $this->service->getAuthUnseenStoryCount();

        return response()->json([
            'status'             => true,
            'data'               => $data,
            'unseen_count'       => $storyCount
        ], count($data) == 0 ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function create(UserStoryCreateRequest $request)
    {
        $response = $this->service->storeStory($request);

        if ($response)
        {
            return response()->json([
                'status' => false,
                'error'  => $response
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'status' => true
        ], Response::HTTP_CREATED);
    }

    public function markSeen($id)
    {
        $response = $this->service->markStoryAsSeen($id);

        return response()->json([
            'status' => true
        ], $response ? Response::HTTP_OK : Response::HTTP_NOT_MODIFIED);
    }

    public function reactStory(UserStoryReactionRequest $request, $id)
    {
        $this->service->storyReaction($request->reaction_id, $id);

        return response()->json([
            'status' => true
        ]);
    }
}
