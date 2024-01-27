<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventAddGuestsRequest;
use App\Http\Requests\EventAddParticipantsRequest;
use App\Http\Requests\EventApproveLockRequest;
use App\Http\Requests\EventCreateRequest;
use App\Http\Requests\EventInventoryCreateRequest;
use App\Http\Requests\EventRatingCreateRequest;
use App\Http\Requests\EventRemoveGuestsRequest;
use App\Http\Requests\EventRemoveParticipantsRequest;
use App\Http\Requests\EventUpdateRequest;
use App\Http\Requests\EventUpdateStatusRequest;
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

    public function index(Request $request)
    {
        $data = $this->service->getAllEvents($request);

        return response()->json([
            'status' => true,
            'data'   => $data
        ], count($data) == 0 ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function getAll()
    {
        $data = $this->service->getParticipantBasedEvents();

        return response()->json([
            'status' => true,
            'data'   => $data
        ], count($data) == 0 ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function eventDesignations($id)
    {
        $data = Cache::remember('event_designation_gradings'.$id, 24*60*60*60, function () use ($id) {
            return $this->service->getDesignationGradings($id);
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], count($data) == 0 ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function getImages($id)
    {
        $data = Cache::remember('event_images'.$id, 24*60*60*30, function () use ($id) {
            return $this->service->getEventImages($id);
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function pendingEvents()
    {
        $data = $this->service->getPendingEvents();

        return response()->json([
            'status' => true,
            'data'   => $data
        ], count($data) == 0 ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function eventExpenseLog($id)
    {
        $data = Cache::remember('event_expenses'.$id, 24*60*60*60, function () use ($id) {
            return $this->service->getExpenseLog($id);
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], !$data ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
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
            Cache::forget('event_designation_gradings'.$id);

            return response()->json(['status' => true], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error'  => $response
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function updateStatus(EventUpdateStatusRequest $request, $id)
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

    public function addGuests(EventAddGuestsRequest $request, $id)
    {
        $response = $this->service->addEventGuests($request, $id);

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
        $response = $this->service->removeEventParticipant($request->user_id, $id);

        return response()->json([
            'status' => true
        ], $response ? Response::HTTP_OK : Response::HTTP_NOT_MODIFIED);
    }

    public function removeGuest(EventRemoveGuestsRequest $request, $id)
    {
        $response = $this->service->removeEventGuest($request->user_id, $id);

        return response()->json([
            'status' => true
        ], $response ? Response::HTTP_OK : Response::HTTP_NOT_MODIFIED);
    }

    public function read($id)
    {
        $data = $this->service->getInfo($id);

        return response()->json([
            'status' => true,
            'data'   => $data
        ], $data ? Response::HTTP_OK : Response::HTTP_NO_CONTENT);
    }

    public function eventParticipants($id)
    {
        $data = Cache::remember('event_participants'.$id, 24*60*60*60, function () use ($id) {
            return $this->service->getEventParticipantList($id);
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], Response::HTTP_OK);
    }

    public function approveEventLock(EventApproveLockRequest $request)
    {
        $response = $this->service->changeApprovalStatus($request);

        return response()->json([
            'status' => true
        ], $response ? Response::HTTP_OK : Response::HTTP_NOT_MODIFIED);
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

    public function eventRating(EventRatingCreateRequest $request, $id)
    {
        $response = $this->service->addRating($request, $id);

        if ($response)
        {
            return response()->json([
                'status' => false,
                'error'  => $response
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'status' => true,
        ], Response::HTTP_CREATED);
    }
}
