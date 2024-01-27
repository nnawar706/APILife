<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventInventoryCreateRequest;
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
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'status' => true,
        ], Response::HTTP_CREATED);
    }

    public function updateInventory(EventInventoryCreateRequest $request, $id, $inventory_id)
    {
        $response = $this->service->updateEventInventory($request, $inventory_id);

        if ($response)
        {
            return response()->json([
                'status' => false,
                'error'  => $response
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'status' => true,
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
