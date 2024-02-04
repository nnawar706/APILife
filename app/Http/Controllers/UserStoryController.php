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
        $this->service->markStoryAsSeen($id);

        return response()->json([
            'status' => true
        ], Response::HTTP_OK);
    }
}
