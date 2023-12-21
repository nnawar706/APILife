<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventAddParticipantsRequest;
use App\Http\Requests\EventCreateRequest;
use App\Http\Requests\EventRemoveParticipantsRequest;
use App\Http\Requests\EventUpdateRequest;
use App\Http\Services\EventService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
    private $service;

    public function __construct(EventService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getAllEvents();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function create(EventCreateRequest $request)
    {
        $response = $this->service->storeNewEvent($request);

        if (!$response)
        {
            return response()->json(['status' => true], Response::HTTP_CREATED);
        }

        return response()->json([
            'status' => false,
            'error'  => $response
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function update(EventUpdateRequest $request, $id)
    {
        $response = $this->service->updateInfo($request, $id);

        if (!$response)
        {
            return response()->json(['status' => true], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error'  => $response
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function addParticipants(EventAddParticipantsRequest $request, $id)
    {
        $response = $this->service->addEventParticipants($request, $id);

        if (!$response)
        {
            return response()->json(['status' => true], Response::HTTP_CREATED);
        }

        return response()->json([
            'status' => false,
            'error'  => $response
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function removeParticipant(EventRemoveParticipantsRequest $request, $id)
    {
        $this->service->removeEventParticipant($request->user_id, $id);

        return response()->json(['status' => true], Response::HTTP_OK);
    }

    public function read($id)
    {
//        $data = Cache::remember('event_info'.$id, 24*60*60*20, function () use ($id) {
//            return $this->service->getInfo($id);
//        });

        return response()->json([
            'status' => true,
            'data'   => $this->service->getInfo($id)
        ], Response::HTTP_OK);
    }
}
