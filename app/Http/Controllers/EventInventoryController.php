<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventInventoryAddParticipantRequest;
use App\Http\Requests\EventInventoryCreateRequest;
use App\Http\Requests\EventInventoryUpdateRequest;
use App\Http\Services\InventoryService;
use Symfony\Component\HttpFoundation\Response;

class EventInventoryController extends Controller
{
    private $service;

    public function __construct(InventoryService $service)
    {
        $this->service = $service;
    }


    public function addInventory(EventInventoryCreateRequest $request, $id)
    {
        $response = $this->service->addEventInventory($request, $id);

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

    public function updateInventory(EventInventoryUpdateRequest $request, $id, $inventory_id)
    {
        $response = $this->service->updateEventInventory($request, $inventory_id);

        return response()->json([
            'status' => true
        ], $response ? Response::HTTP_OK : Response::HTTP_NOT_MODIFIED);
    }

    public function addInventoryParticipants(EventInventoryAddParticipantRequest $request, $id, $inventory_id)
    {
        $this->service->addParticipants($request->users, $inventory_id);

        return response()->json([
            'status' => true
        ], Response::HTTP_OK);
    }

    public function deleteInventory($id, $inventory_id)
    {
        $this->service->removeInventory($inventory_id);

        return response()->json([
            'status' => true
        ], Response::HTTP_OK);
    }
}
