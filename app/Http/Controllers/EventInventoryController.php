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
}
