<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventAddParticipantsRequest;
use App\Http\Requests\EventApproveLockRequest;
use App\Http\Requests\EventCreateRequest;
use App\Http\Requests\EventRemoveParticipantsRequest;
use App\Http\Requests\EventUpdateRequest;
use App\Http\Services\EventService;
use Illuminate\Http\Request;
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
        $data = Cache::remember('events', 24*60*60*7, function () {
            return $this->service->getAllEvents();
        });

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

    public function updateStatus(Request $request, $id)
    {
        $response = $this->service->updateEventStatus($request->event_status_id, $id);

        return response()->json(['status' => true], $response ? Response::HTTP_OK : Response::HTTP_NOT_MODIFIED);
    }

    public function addParticipants(EventAddParticipantsRequest $request, $id)
    {
        $response = $this->service->addEventParticipants($request, $id);

        if (!$response)
        {
            Cache::forget('event_participants'.$id);

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

        Cache::forget('event_participants'.$id);

        return response()->json(['status' => true], Response::HTTP_OK);

    }

    public function read($id)
    {
        $data = Cache::remember('event_info'.$id, 24*60*60*20, function () use ($id) {
            return $this->service->getInfo($id);
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], Response::HTTP_OK);
    }

    public function eventParticipants($event_id)
    {
        $data = Cache::remember('event_participants'.$event_id, 24*60*60*60, function () use ($event_id) {
            return $this->service->getEventParticipantList($event_id);
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], Response::HTTP_OK);
    }

    public function approveEventLock(EventApproveLockRequest $request)
    {
        $response = $this->service->changeApprovalStatus($request);

        return response()->json(['status' => true], $response ? Response::HTTP_OK : Response::HTTP_NOT_MODIFIED);
    }

    public function delete($id)
    {
        if ($this->service->removeEvent($id))
        {
            return response()->json(['status' => true], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error'  => 'Unable to delete event when it has payment data.'
        ], Response::HTTP_BAD_REQUEST);
    }
}
