<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventInventoryCreateRequest;
use App\Http\Services\InventoryService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventInventoryController extends Controller
{
    private $service;

    public function __construct(InventoryService $service)
    {
        $this->service = $service;
    }

    public function getInventory($id, $inventory_id)
    {
        $data = $this->service->getInventoryData($inventory_id);

        return response()->json([
            'status' => true,
            'data'   => $data
        ], $data ? Response::HTTP_OK : Response::HTTP_NO_CONTENT);
    }

    public function getUserInventories()
    {
        $data = $this->service->assignedInventoryList();

        return response()->json([
            'status' => true,
            'data'   => $data
        ], count($data) != 0 ? Response::HTTP_OK : Response::HTTP_NO_CONTENT);
    }


    public function addInventory(EventInventoryCreateRequest $request, $id)
    {
        $this->service->addEventInventory($request, $id);

        return response()->json([
            'status' => true,
        ], Response::HTTP_CREATED);
    }

    public function updateInventory(EventInventoryCreateRequest $request, $id, $inventory_id)
    {
        $response = $this->service->updateEventInventory($request, $inventory_id);

        return response()->json([
            'status' => true,
        ], $response ? Response::HTTP_OK : Response::HTTP_NOT_MODIFIED);
    }

    public function changeStatus(Request $request, $inventory_id)
    {
        $response = $this->service->changeInventoryStatus($request->approval_status, $inventory_id);

        if ($response)
        {
            return response()->json([
                'status' => false,
                'error'  => $response
            ], Response::HTTP_FORBIDDEN);
        }

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
