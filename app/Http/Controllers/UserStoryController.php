<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoryCreateRequest;
use App\Http\Services\UserStoryService;
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

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function create(UserStoryCreateRequest $request)
    {
        $this->service->storeStory($request);

        return response()->json([
            'status' => true
        ], Response::HTTP_CREATED);
    }

    public function markSeen($id)
    {
        $this->service->markStoryAsSeen($id);

        return response()->json([
            'status' => true
        ], Response::HTTP_OK);
    }
}
